<?php
function unhex(string $hex): string{
  $hex = str_replace([' ', "\n", "\r", "\t"], '', $hex);
  return hex2bin($hex);
}

$client_hello = <<<HEX
01 00 00 66 03 03 8a 24 68 75 b8 35 70 f4 4a 7b
d0 7a 0d d7 3a 63 0f 83 33 f4 2a 98 c1 ae 8b 28
b4 43 49 ae ac 6c 00 00 02 00 ae 01 00 00 3b ff
01 00 01 00 00 23 00 00 00 17 00 00 00 0d 00 2a
00 28 04 03 05 03 06 03 08 07 08 08 08 09 08 0a
08 0b 08 04 08 05 08 06 04 01 05 01 06 01 03 03
03 01 03 02 04 02 05 02 06 02
HEX;

$server_hello = <<<HEX
02 00 00 35 03 03 2f ba a6 4c e0 37 69 90 1c c8
7f 08 02 17 72 07 51 06 de 40 6c 2d 45 92 68 49
ab 12 2c a2 77 e7 00 00 ae 00 00 0d ff 01 00 01
00 00 23 00 00 00 17 00 00
HEX;

$server_hello_done = <<<HEX
0e 00 00 00
HEX;

$client_key_exchange = <<<HEX
10 00 00 08 00 06 63 6c 69 65 6e 74
HEX;

$change_cipher_spec = <<<HEX
14 03 03 00 01 01
HEX;

$encrypted_finished = <<<HEX
7d ae 9f 5a 29 3f f6 cb f7 28 69 0a 38 b0 7c e6
06 79 25 2d 09 90 2d 0d 1a d4 ca d0 68 1c 59 90
73 97 42 d9 9a 9e c1 73 ba 7d 8a 67 58 39 51 c2
b3 e7 9f ee c2 89 72 0f 00 6e 95 c5 e3 0c 59 0f
c7 6a e2 f7 da 77 27 6c 72 86 9e c6 51 1e 36 36
HEX;

return [
  'client_hello' => unhex($client_hello),
  'server_hello' => unhex($server_hello),
  'server_hello_done' => unhex($server_hello_done),
  'client_key_exchange' => unhex($client_key_exchange),
  'encrypted_finished' => unhex($encrypted_finished),
  'master_secret' => hex2bin('26079A1FD103D77B8A7031A967652D664CC9A7138C595C0B7D73DF096E86839598C63BE171B7E57AA507D65D2B595F9C'),
];