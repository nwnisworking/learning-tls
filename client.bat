@echo off

where openssl >nul 2>nul

if errorlevel 1 (
    echo OpenSSL is not installed or not found in PATH.
    exit /b 1
)

@REM Cipher mode configuration
echo 0. PSK only
echo 1. RSA + PSK
echo 2. EC + PSK

set /p mode=Select cipher mode: 

@REM PSK configuration
set psk_identity=client
set psk_key=1a2b3c4d5e6f7081

@REM Port configuration
set server_port=9000

@REM Client cipher request

if "%mode%"=="0" (
    set cipher=PSK-AES128-CBC-SHA256
) else (
  if "%mode%"=="1" (
    set cipher=ECDHE-RSA-AES128-SHA256:PSK-AES128-CBC-SHA256
  ) else (
    if "%mode%"=="2" (
      set cipher=ECDHE-ECDSA-AES128-SHA256:PSK-AES128-CBC-SHA256
    ) else (
        echo Invalid mode selected. Use 0, 1, or 2.
        exit /b 1
      )
    )
  )

echo Using cipher(s): %cipher%

@REM PSK version

@REM openssl s_client ^
@REM   -connect localhost:%server_port% ^
@REM   -tls1_2 ^
@REM   -cipher %cipher% ^
@REM   -no_etm ^
@REM   -psk_identity %psk_identity% ^
@REM   -psk %psk_key% ^

openssl s_client ^
  -connect localhost:%server_port% ^
  -tls1_2 ^
  -cipher %cipher% ^
  -no_etm ^
  -curves secp256r1 ^
  -cipher ECDHE-RSA-AES128-GCM-SHA256 ^
  -no_ticket