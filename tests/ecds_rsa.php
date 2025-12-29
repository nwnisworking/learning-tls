<?php
require_once 'utils.php';

$cipher = 0xc02f; // TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256

$groups = [
  0x0017 // secp256r1
];
$signatures = [
  0x0401 // rsa_pkcs1_sha256
];

$socket = client_socket();

$client_hello = handshake_client_hello(
  $cipher,
  extensions : 
    pack('n*', 10, count($groups) * 2 + 2, count($groups) * 2, ...$groups).
    pack('n*', 13, count($signatures) * 2 + 2, count($signatures) * 2, ...$signatures)
);

socket_write($socket, record_header(RecordType::HANDSHAKE, $client_hello));

foreach(parse_record(socket_read($socket, 8192)) as $record){
  if($record['type'] === RecordType::HANDSHAKE->value){
    $handshake_type = ord($record['body'][0]);
    $body = $record['body'];

    switch($handshake_type){
      case HandshakeType::SERVER_HELLO->value : $server_hello = $body; break;
      case HandshakeType::CERTIFICATE->value : $server_certificate = $body; break;
      case HandshakeType::SERVER_HELLO_DONE->value : $server_hello_done = $body; break;
      case HandshakeType::SERVER_KEY_EXCHANGE->value : $server_key_exchange = $body; break;
    }
  }
}

$client_random = substr($client_hello, 6, 32);
$server_random = substr($server_hello, 6, 32);

$client_private_key = openssl_pkey_new([
  'private_key_type' => OPENSSL_KEYTYPE_EC,
  'curve_name' => 'prime256v1'
]);

$server_param = substr($server_key_exchange, 4, 65 + 4);
$server_signature = substr($server_key_exchange, 65 + 8 + 4);
$server_extract_certificate = substr($server_certificate, 10);
$server_extract_certificate = "-----BEGIN CERTIFICATE-----\n"
  . chunk_split(base64_encode($server_extract_certificate), 64, "\n")
  . "-----END CERTIFICATE-----\n";

$server_extract_certificate = openssl_get_publickey($server_extract_certificate);
echo "Is legitimate: " . (openssl_verify(
  $client_random . $server_random . $server_param,
  $server_signature,
  $server_extract_certificate,
  OPENSSL_ALGO_SHA256
) ? 'true' : 'false') . "\n";

$server_public_key = hex2bin('3059301306072A8648CE3D020106082A8648CE3D030107034200').substr($server_key_exchange, 8, 65);

$server_public_key = chunk_split(base64_encode($server_public_key), 64, "\n");

$server_public_key = 
  "-----BEGIN PUBLIC KEY-----\n"
  . $server_public_key
  . "-----END PUBLIC KEY-----\n";

$server_public_key = openssl_get_publickey($server_public_key);

$pre_master_secret = openssl_pkey_derive($server_public_key, $client_private_key);

$master_secret = tls_prf(
  'master secret',
  $pre_master_secret,
  "$client_random$server_random",
  48
);

$key_block = tls_prf(
  'key expansion',
  $master_secret,
  "$server_random$client_random",
  128
);

$client = [
  'enc' => substr($key_block, 0, 16),
  'iv' => substr($key_block, 32, 4)
];

$server = [
  'enc' => substr($key_block, 16, 16),
  'iv' => substr($key_block, 36, 4)
];

$client_public_key = openssl_pkey_get_details($client_private_key)['ec'];
$client_key_exchange = handshake_client_key_exchange("\x4".$client_public_key['x'] . $client_public_key['y']);

$handshake_messages = $client_hello . $server_hello . $server_certificate . $server_key_exchange . $server_hello_done . $client_key_exchange;

$finished_message = handshake_finished('client', $master_secret, $handshake_messages);

$seq = pack('J', 0);
$nonce = substr($client['iv'], 0, 4) . $seq;
$aad = $seq . chr(RecordType::HANDSHAKE->value) . pack('n', 0x0303) . pack('n', strlen($finished_message));

$ciphertext = openssl_encrypt(
  $finished_message,
  'aes-128-gcm',
  $client['enc'],
  OPENSSL_RAW_DATA,
  $nonce,
  $tag,
  $aad
);

$payload = $seq . $ciphertext . $tag;

print_hex($payload);
socket_write(
  $socket, 
  record_handshake($client_key_exchange).
  record_change_cipher_spec().
  record_header(RecordType::HANDSHAKE, $payload)
);

// Supposed to parse 
socket_read($socket, 8192);

$data = "test\r\n";
$seq = pack('J', 1);
$nonce = substr($client['iv'], 0, 4) . $seq;
$aad = $seq . chr(RecordType::APPLICATION_DATA->value) . pack('n', 0x0303) . pack('n', strlen($data));

$ciphertext = openssl_encrypt(
  $data,
  'aes-128-gcm',
  $client['enc'],
  OPENSSL_RAW_DATA,
  $nonce,
  $tag,
  $aad
);

$payload = $seq . $ciphertext . $tag;

socket_write(
  $socket, 
  record_header(RecordType::APPLICATION_DATA, $payload)
);

var_dump(bin2hex($server['iv']));
sleep(30);