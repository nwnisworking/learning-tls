<?php
require_once 'utils.php';

$client_hello = <<<HEX
01 00 00 70 03 03 39 15 c2 33 27 71 87 0b 44 b1
4f 44 9c e6 62 bc 2f 65 be 18 d1 9b 8b 2f c5 a5
1f a5 63 80 ea 51 00 00 02 c0 2f 01 00 00 45 ff
01 00 01 00 00 0b 00 02 01 00 00 0a 00 04 00 02
00 17 00 17 00 00 00 0d 00 2a 00 28 04 03 05 03
06 03 08 07 08 08 08 09 08 0a 08 0b 08 04 08 05
08 06 04 01 05 01 06 01 03 03 03 01 03 02 04 02
05 02 06 02
HEX;

$server_hello = <<<HEX
02 00 00 57 03 03 69 3e e9 85 80 89 f4 08 46 f4
af c8 3c c9 29 91 1e 7f 84 b5 6e 4f b9 db 25 09
a1 67 90 c8 3d fd 20 0d 65 0b c3 89 56 2f 65 87
a6 ff 3a f8 0a 67 79 58 b6 d0 3a b4 d8 fe 4b 6e
5a 06 c6 1c 06 45 57 c0 2f 00 00 0f ff 01 00 01
00 00 0b 00 02 01 00 00 17 00 00
HEX;

$server_certificate = <<<HEX
0b 00 03 13 00 03 10 00 03 0d 30 82 03 09 30 82
01 f1 a0 03 02 01 02 02 14 51 e0 e4 a9 aa d9 a4
55 28 3f c8 4f 59 e6 79 09 e3 c2 b4 e9 30 0d 06
09 2a 86 48 86 f7 0d 01 01 0b 05 00 30 14 31 12
30 10 06 03 55 04 03 0c 09 6c 6f 63 61 6c 68 6f
73 74 30 1e 17 0d 32 35 31 32 32 36 31 39 35 34
33 33 5a 17 0d 32 36 31 32 32 36 31 39 35 34 33
33 5a 30 14 31 12 30 10 06 03 55 04 03 0c 09 6c
6f 63 61 6c 68 6f 73 74 30 82 01 22 30 0d 06 09
2a 86 48 86 f7 0d 01 01 01 05 00 03 82 01 0f 00
30 82 01 0a 02 82 01 01 00 be 31 7c af 7d 6e fe
cf 91 d0 a1 b4 9d b7 d8 1f 84 4d d8 38 84 2b 39
35 41 24 b8 92 95 48 16 94 27 f9 a0 07 3e 8e 59
48 32 86 b1 fe 6d e3 33 0f 24 da 86 71 59 48 46
c3 ca df cb 63 0c 7c 48 fd 0e 53 e0 2e c9 06 b7
74 17 a3 f2 a6 8f 84 c3 39 68 07 e5 48 b1 2d 8e
0e 3a eb 23 02 b7 d7 6f a5 47 02 7a 86 a4 eb 91
48 95 f0 8b 00 0f 9e 59 f6 e7 26 3c 36 61 da a7
a0 a5 4c 81 f0 40 52 c2 a2 e3 cd 5d 43 cf 68 27
89 62 9b 56 6c ab 1d ad 64 cd 72 5b ab a9 d7 e0
0b e3 de 59 e2 78 07 e2 39 9e 07 2b 8b 69 87 69
1c b3 25 37 76 42 49 2c 14 d0 83 d7 80 fe cf 82
1a d5 57 e6 d7 50 79 39 46 3e b6 aa d8 bf 3a b1
32 df 5c 65 5d c7 bc e8 37 10 61 a2 18 a3 89 80
28 d9 90 08 98 bb 06 9c 64 81 81 25 3f 78 46 70
d3 b7 b5 b2 48 49 72 81 a6 01 75 42 bd 34 59 34
52 c2 b7 b4 77 1a 5b 6b bd 02 03 01 00 01 a3 53
30 51 30 1d 06 03 55 1d 0e 04 16 04 14 1d 29 6f
6b 76 63 7f 1a a5 70 58 54 57 a7 50 18 d2 3c 81
22 30 1f 06 03 55 1d 23 04 18 30 16 80 14 1d 29
6f 6b 76 63 7f 1a a5 70 58 54 57 a7 50 18 d2 3c
81 22 30 0f 06 03 55 1d 13 01 01 ff 04 05 30 03
01 01 ff 30 0d 06 09 2a 86 48 86 f7 0d 01 01 0b
05 00 03 82 01 01 00 71 ae 38 d2 d7 ae 0f ac 84
f3 06 dd 30 7f 56 ab cd a1 2c 21 6c 58 f8 95 ad
4a 80 df 74 25 0a b3 f7 13 18 8f 40 d6 ac 94 56
77 62 ef 91 10 6a 2b c2 44 6c 5b 68 ff 5c b3 3b
98 aa 7f aa 67 d4 9b c7 02 7c 7a 8e 46 88 67 92
ee cc f5 16 5e 9b 4b 54 bf 3c 19 88 04 3b fc 0f
20 a1 fb 55 fd 87 1e 48 c8 5b da 1a 7e 82 4d 52
14 58 18 83 a5 d7 5c ca d5 70 c4 45 2c 36 a5 53
98 69 16 ff 30 4f 9d 62 d3 b4 d5 c5 8c 7d 61 8e
bf aa a1 21 e7 d9 e1 f8 7b 88 c3 74 b6 e7 92 ca
7a ed de 7b 89 1c 6b fb 3a 3b 6d 35 15 24 81 33
0e 86 2e a8 0c c0 4d 23 0c a8 e6 7d 9d ac 28 b3
e1 db d8 ee d3 97 a2 f0 3b c0 cc fe cf 44 14 d9
b2 79 af 26 ef a6 e0 0e eb 25 ad e1 2e 13 a0 18
a0 c9 48 f9 1d ea 2b 13 5d 72 15 ff 4a fa 2a b5
dd a6 0d 39 6f cd 6f be 73 15 16 1d f9 c3 56 3c
bc d5 0e a1 91 5a d4
HEX;

$server_key_exchange = <<<HEX
0c 00 01 49 03 00 17 41 04 96 9d 9a 79 a9 b4 89
ae c7 c8 ff d6 37 9a db f4 8e ef cb 7c a6 9a fe
53 4e 2c dd 93 06 e4 fd 36 41 58 79 8b d0 7d 92
7a 41 63 b2 3a 13 bf 97 ac fc d4 f9 da ac 10 bf
a4 31 3c fb 4d 75 63 be 5e 08 04 01 00 13 38 9e
5a ba 33 82 ac bb 30 86 6b 84 78 6f 3d bd 8e 7e
29 a4 83 b3 d4 80 04 52 c7 dc 05 a1 24 a9 7f 25
f5 60 7f aa 53 aa 92 0c fe 01 dd 31 b2 10 dd 13
38 a8 65 1d b2 fa 5a 61 09 13 d9 74 b8 a0 b1 42
53 c8 54 c6 0e 68 cb 89 e8 87 03 b5 01 0a 4e f6
2a b0 ad 03 7a d2 a0 c2 f4 84 08 40 f4 a9 b1 5e
77 16 a1 94 60 73 14 fb 6a a7 e4 84 68 54 71 79
19 ac 23 7c 89 51 45 ba d4 d5 82 8b 19 dc 32 19
04 e5 fc e2 7c 26 56 0e 49 38 19 1e b8 6e 2d 06
fa e6 70 3b 2f a4 aa e8 cd c2 6c 54 23 31 5d 68
dd 12 a6 35 45 48 a3 61 1b eb 1f de 91 a9 31 df
b7 c7 e1 0d d7 52 71 4c d6 c3 20 d7 ab 40 14 2b
f2 76 ef 73 bb c4 07 ff ab ff b8 68 1f 93 0a e5
f3 52 25 84 fa fe 26 ae c3 4a 8c 11 f5 f0 2e ba
fd fa 32 89 21 31 18 0b 15 d2 69 c7 85 22 f0 10
5d 8e cc 45 8a 30 c7 43 d9 cc bb 30 9c
HEX;

$server_hello_done = <<<HEX
0e 00 00 00
HEX;

$client_key_exchange = <<<HEX
10 00 00 42 41 04 a8 8a 96 35 e5 1b 9d 0c b9 b9
f9 3b d5 c1 fb b7 d6 f1 5b 75 a7 b6 8a 06 40 77
7d ab 8b 6d 59 f1 ff 1c 23 cc 9f 24 8b ba bd e0
b7 30 eb 65 98 12 e1 3d a7 f2 59 dc d7 89 9d f0
5e a5 61 33 35 b9
HEX;

$client_finished = <<<HEX
a6 9c 26 94 9c 6a 59 d3 1b f8 8c cf ed 54 9a 4c
b2 c1 57 3a 07 06 ea df 13 ca 32 33 46 49 6e 30
2c e5 f0 e4 43 1f 6b 4c
HEX;

$encrypted_message = <<<HEX
a6 9c 26 94 9c 6a 59 d4 ed 0b 00 6e 98 b4 2c 65
a6 15 a5 31 15 ee 5d 69 2b 21 5e 03 e3 1b
HEX;

$actual_master_secret = hex2bin('D45B2C8EEAC46819508A39252D4094E6D08A570A1236320C059CD03046E6295CE27BB84AB45C64B248C4793921C4958E');

$client_hello = unhex($client_hello);
$server_hello = unhex($server_hello);
$server_certificate = unhex($server_certificate);
$server_key_exchange = unhex($server_key_exchange);
$server_hello_done = unhex($server_hello_done);
$client_key_exchange = unhex($client_key_exchange);
$client_finished = unhex($client_finished);
$encrypted_message = unhex($encrypted_message);

$client_random = substr($client_hello, 6, 32);
$server_random = substr($server_hello, 6, 32);

// This part is abandoned because I can't retrieve the private key for the certificate above.