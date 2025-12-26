<?php
require_once 'utils.php';
$data = require_once 'openssl_psk_no_etm.php';

$psk = hex2bin("1a2b3c4d5e6f7081");
$psk_len = strlen($psk);
$client_random = substr($data['client_hello'], 6, 32);
$server_random = substr($data['server_hello'], 6, 32);

$handshake_messages = $data['client_hello'] . 
  $data['server_hello'] . 
  $data['server_hello_done'] . 
  $data['client_key_exchange']
;

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
  $server_random,
  $handshake_messages
);

$iv = substr($data['encrypted_finished'], 0, 16);
$decrypted_finished = openssl_decrypt(
  substr($data['encrypted_finished'], 16),
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA,
  $iv
);

$finished_message = substr($decrypted_finished, 0, 16);
$verify_data = substr($finished_message, 4, 12);
$mac = substr($decrypted_finished, 16, 32);
// Verification

$computed_verify_data = tls_prf(
  "client finished",
  $master_secret,
  hash('sha256', $handshake_messages, true),
  12
);

echo "Computed Verify Data: " . bin2hex($computed_verify_data) . PHP_EOL;
echo "Received Verify Data: " . bin2hex($verify_data) . PHP_EOL;

$computed_mac = hash_hmac(
  'sha256',
  pack('JCn2', 0, 0x16, 0x0303, strlen($finished_message)) . $finished_message,
  $client['mac'],
  true
);

echo "Computed MAC: " . bin2hex($computed_mac) . PHP_EOL;
echo "Received MAC: " . bin2hex($mac) . PHP_EOL;
echo "Computed MAC size: " . strlen($computed_mac) . PHP_EOL;

echo "Encrypted Finished size: " . strlen(substr($data['encrypted_finished'], 16)) . PHP_EOL;

echo "Decrypted Finished size: " . strlen($decrypted_finished) . PHP_EOL;
