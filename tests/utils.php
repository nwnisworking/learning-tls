<?php
declare(strict_types=1);

enum HandshakeType : int{
  case CLIENT_HELLO = 1;
  case SERVER_HELLO = 2;
  case FINISHED = 20;
  case CLIENT_KEY_EXCHANGE = 16;
  case SERVER_HELLO_DONE = 14;

  case CERTIFICATE = 11;

  case SERVER_KEY_EXCHANGE = 12;
}

enum RecordType : int{
  case CHANGE_CIPHER_SPEC = 20;
  case HANDSHAKE = 22;
  case APPLICATION_DATA = 23;
  case ALERT = 21;
}

enum Version : int{
  case TLS_10 = 0x0301;
  case TLS_11 = 0x0302;
  case TLS_12 = 0x0303;
  case TLS_13 = 0x0304;
}

/**
 * Create a handshake header
 * @param HandshakeType $type
 * @param string $body
 * @return string
 */
function handshake_header(HandshakeType $type, string $body): string{
  return pack('Na*', $type->value << 24 | strlen($body), $body);
}

/**
 * Create a record header
 * @param RecordType $type
 * @param string $body
 * @param Version $version
 * @return string
 */
function record_header(RecordType $type, string $body, Version $version = Version::TLS_12): string{
  return pack('Cn2a*', $type->value, $version->value, strlen($body), $body);
}

/**
 * Creates a Client Hello handshake
 * @param int $cipher
 * @param string $session_id
 * @param string $extensions
 * @return string
 */
function handshake_client_hello(int $cipher, string $session_id = '', string $extensions = ''): string{
  $handshake = pack(
    'na*Ca*n3',
    Version::TLS_12->value,
    random_bytes(32),
    strlen($session_id),
    $session_id,
    2,
    $cipher,
    1 << 8
  );

  if($extensions !== ''){
    $handshake .= pack('na*', strlen($extensions), $extensions);
  }

  return handshake_header(HandshakeType::CLIENT_HELLO, $handshake);
}

function handshake_finished(string $side, string $master_secret, string $messages): string{
  return handshake_header(HandshakeType::FINISHED, tls_prf(
    "$side finished",
    $master_secret,
    hash('sha256', $messages, true),
    12
  ));
}

/**
 * Create a client key exchange
 * @param string $identity
 * @return string
 */
function handshake_client_key_exchange(string $identity): string{
  $len = strlen($identity);

  return handshake_header(HandshakeType::CLIENT_KEY_EXCHANGE, pack('Ca*', $len, $identity));
}

/**
 * Create a Change Cipher Spec record
 * @return string
 */
function record_change_cipher_spec(Version $version = Version::TLS_12): string{
  return record_header(RecordType::CHANGE_CIPHER_SPEC, "\x01", $version);
}

// function record_application_data(string $data, Version $version = Version::TLS_12): string{
//   return record_header(RecordType::APPLICATION_DATA, )
// }

/**
 * Create a handshake record
 * @param string $handshake
 * @return string
 */
function record_handshake(string $handshake): string{
  return record_header(RecordType::HANDSHAKE, $handshake);
}

/**
 * Parse record
 * @param string $data
 * @return Generator<mixed, array{body: string, type: mixed, version: mixed, mixed, void>}
 */
function parse_record(string $data): Generator{
  $offset = 0;
  $len = strlen($data);

  while($offset < $len){
    $header = unpack('Ctype/nversion/nlength', substr($data, $offset, 5));
    $offset += 5;

    $body = substr($data, $offset, $header['length']);
    $offset += $header['length'];

    yield [
      'type' => $header['type'],
      'version' => $header['version'],
      'body' => $body
    ];
  }
}

/**
 * Derive keys from pre master secret, client random, server random, and handshake messages
 * @param string $pre_master_secret
 * @param string $client_random
 * @param string $server_random
 * @param string $handshake_messages
 * @return array{client: array{enc: string, mac: string, master_secret: string, server: array{enc: string, mac: string}}}
 */
function derive_keys(string $pre_master_secret, string $client_random, string $server_random, string $handshake_messages = ''): array{
  if($handshake_messages !== ''){
    $master_secret = tls_prf(
      "extended master secret",
      $pre_master_secret,
      hash('sha256', $handshake_messages, true),
      48
    );
  }
  else{
    $master_secret = tls_prf(
      "master secret",
      $pre_master_secret,
      "$client_random$server_random",
      48
    );

  }

  $key_block = tls_prf(
    "key expansion",
    $master_secret,
    "$server_random$client_random",
    128
  );

  return [
    'client' => [
      'mac' => substr($key_block, 0, 32),
      'enc' => substr($key_block, 64, 16),
    ],
    'server' => [
      'mac' => substr($key_block, 32, 32),
      'enc' => substr($key_block, 80, 16),
    ],
    'master_secret' => $master_secret
  ];
}

/**
 * TLS PRF
 * @param string $label
 * @param string $key
 * @param string $data
 * @param int $length
 * @return string
 */
function tls_prf(string $label, string $key, string $data, int $length): string{
	$seed = "$label$data";
	$a0 = $seed;
	$output = '';

	while(strlen($output) < $length){
		$a1 = hash_hmac('sha256', $a0, $key, true);
		$p1 = hash_hmac('sha256', "$a1$seed", $key, true);
		$output .= $p1;
		$a0 = $a1;
	}

	return substr($output, 0, $length);
}

/**
 * Create a client connection 
 * @return bool|resource|Socket
 */
function client_socket(): Socket{
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  socket_connect($socket, 'localhost', 9000);

  return $socket;
}

/**
 * Print hexadecimal
 * @param string $data
 * @return void
 */
function print_hex(string $data): void{
  foreach(str_split(bin2hex($data), 2) as $i=>$byte){
    echo $byte . ((($i + 1) % 16 === 0) ? "\n" : ' ');
  }

  echo "\n";
}

function mac_data(int $seq, string $key, string $record_with_handshake): string{
  return hash_hmac(
    'sha256',
    pack('Ja*', $seq, $record_with_handshake),
    $key,
    true
  );
}

function pad_text(string $text, $block): string{
  $pad_len = $block - (strlen(string: $text) + 1) % $block;
  return $text . str_repeat(chr($pad_len), $pad_len + 1);
}

function unhex(string $hex): string{
  $hex = str_replace([' ', "\n", "\r", "\t"], '', $hex);
  return hex2bin($hex);
}

function extension_header(int $type, ?string $body = null): string{
  return pack('n2a*', $type, $body === null ? 0 : strlen($body), $body ?? '');
}