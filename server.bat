@echo off

where openssl >nul 2>nul

if errorlevel 1 (
    echo OpenSSL is not installed or not found in PATH.
    exit /b 1
)

set dir=%~dp0
set key_dir=%dir%keys

@REM PSK configuration
set psk_identity=client
set psk_key=1a2b3c4d5e6f7081

@REM Port configuration
set server_port=9000

@REM RSA key and certificates
set rsa_key=%key_dir%\rsa.key
set rsa_cert=%key_dir%\rsa.crt

@REM EC key and certificates
set ec_key=%key_dir%\ec.key
set ec_cert=%key_dir%\ec.crt

@REM Launch the server
openssl s_server ^
  -accept %server_port% ^
  -psk_identity %psk_identity% ^
  -psk %psk_key% ^
  -cert %rsa_cert% ^
  -key %rsa_key% ^
  -cert %ec_cert% ^
  -key %ec_key% ^
  -psk_identity %psk_identity% ^
  -psk %psk_key% ^
  -tls1_2 ^
  -debug ^
  -msg ^
  -state

echo Server started on port %server_port%