@echo off

where openssl >nul 2>nul

if errorlevel 1 (
  echo OpenSSL is not installed or not found in PATH.
  exit /b 1
)

set dir=%~dp0
set key_dir=%dir%keys

if not exist "%key_dir%" mkdir keys

openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out %key_dir%\rsa.key 2>nul
openssl pkey -in %key_dir%\rsa.key -pubout -out %key_dir%\rsa.pub
openssl req -new -x509 -key %key_dir%\rsa.key -out %key_dir%\rsa.crt -days 365 -subj "/CN=localhost"

echo RSA keys and certificate generated.

openssl genpkey -algorithm EC -pkeyopt ec_paramgen_curve:prime256v1 -out %key_dir%\ec.key 2>nul
openssl pkey -in %key_dir%\ec.key -pubout -out %key_dir%\ec.pub
openssl req -new -x509 -key %key_dir%\ec.key -out %key_dir%\ec.crt -days 365 -subj "/CN=localhost"

echo EC keys and certificate generated.

echo All keys and certificates are stored in the "%key_dir%" directory.