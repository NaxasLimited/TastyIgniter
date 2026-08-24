@echo off
setlocal
set "PROJECT_DIR=%~dp0"
set "PHP_BIN=%PROJECT_DIR%.tools\php-8.5.9-nts-Win32-vs17-x64\php.exe"

if not exist "%PHP_BIN%" (
    echo Portable PHP was not found at:
    echo %PHP_BIN%
    echo.
    echo Please run setup again or install PHP 8.4+.
    exit /b 1
)

cd /d "%PROJECT_DIR%"
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000
