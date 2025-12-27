<?php
require_once 'utils.php';

$psk = hex2bin("1a2b3c4d5e6f7081");
$psk_len = strlen($psk);

$socket = client_socket();
$client_hello = client_hello_handshake(0xAE, 0x0303);
$client_key_exchange = client_key_exchange_handshake("client");

socket_write($socket, record_handshake($client_hello));

foreach(parse_record(socket_read($socket, 8192)) as $record){
  if($record['type'] === 22){
    $handshake_type = ord($record['body'][0]);
    $body = $record['body'];

    switch($handshake_type){
      case 2: $server_hello = $body; break;
      case 14: $server_hello_done = $body; break;
    }
  }
}

$client_random = substr($client_hello, 6, 32);
$server_random = substr($server_hello, 6, 32);

[
  'client' => $client, 
  'server' => $server, 
  'master_secret' => $master_secret
] = derive_keys(
  pack(
    'na*na*', 
    $psk_len, 
    str_repeat("\x00", $psk_len),
    $psk_len,
    $psk
  ), 
  $client_random, 
  $server_random
);

$iv = random_bytes(16);
$handshake_messages = $client_hello . $server_hello . $server_hello_done . $client_key_exchange;

$verify_data = tls_prf(
  "client finished",
  $master_secret,
  hash('sha256', $handshake_messages, true),
  12
);

$finished_message = pack('Na*', 20 << 24 | 12, $verify_data);
$mac = hash_hmac(
  'sha256',
  pack('JCn2', 0, 0x16, 0x0303, strlen($finished_message)) . $finished_message,
  $client['mac'],
  true
);

$plaintext = $finished_message . $mac;
$pad_len = 16 - (strlen($plaintext) + 1) % 16;
$plaintext .= str_repeat(chr($pad_len), $pad_len + 1);

$ciphertext = openssl_encrypt(
  $plaintext,
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
  $iv
);


echo "Encrypted Finished size: " . strlen($ciphertext) . PHP_EOL;
$decrypted_finished = openssl_decrypt(
  $ciphertext,
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA,
  $iv
);

echo "Decrypted Finished size: " . strlen($decrypted_finished) . PHP_EOL;

socket_write($socket, 
  record_handshake($client_key_exchange, 0x0303).
  record_change_cipher_spec(0x0303).
  record_handshake(
  $iv . $ciphertext, 0x0303)
);

