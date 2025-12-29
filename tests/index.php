<?php
require_once 'utils.php';
require_once 'constants.php';

$config = require_once 'config.php';

$socket = client_socket();
$client_hello = handshake_client_hello(0xAE);
$client_key_exchange = handshake_client_key_exchange($config['psk_identity']);

socket_write($socket, record_header(RecordType::HANDSHAKE, $client_hello));

foreach(parse_record(socket_read($socket, 8192)) as $record){
  if($record['type'] === RecordType::HANDSHAKE->value){
    $handshake_type = ord($record['body'][0]);
    $body = $record['body'];

    switch($handshake_type){
      case HandshakeType::SERVER_HELLO->value : $server_hello = $body; break;
      case HandshakeType::SERVER_HELLO_DONE->value : $server_hello_done = $body; break;
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
    strlen($config['psk_key']), 
    str_repeat("\x00", strlen($config['psk_key'])),
    strlen($config['psk_key']),
    $config['psk_key']
  ), 
  $client_random, 
  $server_random
);

$iv = random_bytes(16);
$handshake_messages = $client_hello . $server_hello . $server_hello_done. $client_key_exchange;

$finished_message = handshake_finished('client', $master_secret, $handshake_messages);
$mac = mac_data(0, $client['mac'], record_header(RecordType::HANDSHAKE, $finished_message));

$ciphertext = openssl_encrypt(
  pad_text("$finished_message$mac", 16),
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
  $iv
);

socket_write(
  $socket, 
  record_handshake($client_key_exchange).
  record_change_cipher_spec().
  record_handshake("$iv$ciphertext")
);

$response = socket_read($socket, 8192);

// Server's ChangeCipherSpec and Finished
foreach(parse_record($response) as $record){
  if($record['type'] === RecordType::HANDSHAKE->value){
    $iv = substr($record['body'], 0, 16);
    $ciphertext = substr($record['body'], 16);
    $plaintext = openssl_decrypt(
      $ciphertext,
      'aes-128-cbc',
      $server['enc'],
      OPENSSL_RAW_DATA,
      $iv
    );

    $handshake_messages .= $finished_message;
    $finished_message = substr($plaintext, 0, 16);
    $verify_data = substr($finished_message, 4, 12);

    $mac = substr($plaintext, 16, 32);

    $expected_mac = mac_data(0, $server['mac'], record_header(RecordType::HANDSHAKE, $finished_message));

    echo "Server MAC: ".(hash_equals($mac, $expected_mac) ? "valid\n" : "invalid\n");
    echo "Server verify data: ".(hash_equals($verify_data, tls_prf(
      SERVER_FINISHED_LABEL,
      $master_secret,
      hash('sha256', $handshake_messages, true),
      12
    )) ? "valid\n" : "invalid\n");
  }
}

$data = "test\r\n";
$iv = random_bytes(16);

$mac = mac_data(1, $client['mac'], record_header(RecordType::APPLICATION_DATA, $data));

$plaintext = $data . $mac;
$plaintext = pad_text($plaintext, 16);

$ciphertext = openssl_encrypt(
  $plaintext,
  'aes-128-cbc',
  $client['enc'],
  OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
  $iv
);

socket_write(
  $socket,
  record_header(RecordType::APPLICATION_DATA, $iv . $ciphertext)
);

socket_read($socket, 8192); // Ignore server response for this test