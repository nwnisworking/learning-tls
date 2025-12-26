<?php

function record_handshake(string $data, int $version = 0x0301): string{
  return pack('Cn2a*', 0x16, $version, strlen($data), $data);
}

function record_change_cipher_spec(): string{
  return pack('Cn2a*', 0x14, 0x0303, 1, "\x01");
}

function client_key_exchange_handshake(string $identity): string{
  $len = strlen($identity);

  return pack('Nna*', 16 << 24 | $len + 2, $len, $identity);
}

function client_hello_handshake(int $cipher, string $session_id = '', string $extensions = ''): string{
  $handshake = pack(
    'na*Ca*n3', 
    0x0303,
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

  return pack('Na*', 1 << 24 | strlen($handshake), $handshake);
}

function change_cipher_spec_record(): string{
  return pack('Cn2a*', 20, 0x0303, 1, "\x01");
}

function client_socket(): Socket{
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  socket_connect($socket, '127.0.0.1', 9000);

  return $socket;
}

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
      $client_random . $server_random,
      48
    );

  }

  $key_block = tls_prf(
    "key expansion",
    $master_secret,
    $server_random . $client_random,
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

function tls_prf(string $label, string $key, string $data, int $length): string{
	$seed = $label . $data;
	$a0 = $seed;
	$output = '';

	while(strlen($output) < $length){
		$a1 = hash_hmac('sha256', $a0, $key, true);
		$p1 = hash_hmac('sha256', $a1 . $seed, $key, true);
		$output .= $p1;
		$a0 = $a1;
	}

	return substr($output, 0, $length);
}

function finished_handshake(string $side, string $master_secret, string $handshake_messages): string{
  $verify_data = tls_prf(
    "$side finished",
    $master_secret,
    hash('sha256', $handshake_messages, true),
    12
  );

  return pack('Na*', 20 << 24 | strlen($verify_data), $verify_data);
}

function tls_cbc_pad(string $data, int $block_size = 16): string {
    $pad_len = $block_size - ((strlen($data) + 1) % $block_size);
    return $data . str_repeat(chr($pad_len), $pad_len + 1);
}