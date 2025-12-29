<?php
require_once 'utils.php';

define('PSK_KEY', hex2bin('1a2b3c4d5e6f7081'));

$client_hello = <<<HEX
01 00 00 66 03 03 ca ec 42 f2 52 a3 ee 67 a6 e9
95 07 96 ce ad 5b 13 1c 32 08 e7 6c 4e ff e6 5b
7a 00 d8 80 3a f8 00 00 02 00 ae 01 00 00 3b ff
01 00 01 00 00 23 00 00 00 17 00 00 00 0d 00 2a
00 28 04 03 05 03 06 03 08 07 08 08 08 09 08 0a
08 0b 08 04 08 05 08 06 04 01 05 01 06 01 03 03
03 01 03 02 04 02 05 02 06 02
HEX;

$server_hello = <<<HEX
02 00 00 35 03 03 6d b6 50 75 cc 41 ae 78 a0 cb
97 f3 8d 2e fe 8d 41 a8 e3 90 8a 4b 11 ca fe 98
81 d8 22 77 ac 8f 00 00 ae 00 00 0d ff 01 00 01
00 00 23 00 00 00 17 00 00
HEX;

$server_hello_done = <<<HEX
0e 00 00 00
HEX;

$client_key_exchange = <<<HEX
10 00 00 08 00 06 63 6c 69 65 6e 74
HEX;

$client_hello = unhex($client_hello);
$server_hello = unhex($server_hello);
$server_hello_done = unhex($server_hello_done);
$client_key_exchange = unhex($client_key_exchange);

$client_random = substr($client_hello, 6, 32);
$server_random = substr($server_hello, 6, 32);

$handshake_messages = $client_hello . $server_hello . $server_hello_done . $client_key_exchange;

[
  'client' => $client, 
  'server' => $server, 
  'master_secret' => $master_secret
] = derive_keys(
  pack(
    'na*na*',
    strlen(PSK_KEY),
    str_repeat("\x00", strlen(PSK_KEY)),
    strlen(PSK_KEY),
    PSK_KEY
  ),
  $client_random,
  $server_random,
  $handshake_messages
);

// This is where we test decryption of encrypted data
$app_data = <<<HEX
c3 71 d5 34 a3 84 1b aa 29 66 f4 71 18 c0 15 b2
d9 69 4d 8f 44 91 3f bc a2 51 6f 35 f9 7b 4a da
f6 02 69 af 09 0c a8 56 e0 b4 bc ea 23 d1 ff 3d
13 bf b5 ee 73 f8 a5 ac 16 f1 7f f2 54 87 1a af
HEX;

$app_data = unhex($app_data);

$iv = substr($app_data, 0, 16);
$ciphertext = substr($app_data, 16);

$plaintext = openssl_decrypt(
  $ciphertext,
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
  $iv
);

$data = substr($plaintext, 0, 6);
$mac = substr($plaintext, 6, 32);

echo "Decrypted Application Data:\n";
print_hex($plaintext);

echo "Actual MAC:\n";
print_hex($mac);

$computed_mac = mac_data(1, $client['mac'], record_header(RecordType::APPLICATION_DATA, $data));

echo "Computed MAC:\n";
print_hex($computed_mac);

$plaintext = $data;
$plaintext.= $mac;

$plaintext = pad_text($plaintext, 16);

echo "Constructed padded data for verification:\n";
print_hex($plaintext);

// When the record is sent from client to server, the sequence number is 1 and the size of the data (text + mac + padding) is 64 bytes