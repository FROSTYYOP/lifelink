@echo off
title LifeLink Servers
echo ========================================================
echo   LifeLink - Standalone Server Launcher (No XAMPP)
echo ========================================================
echo.

:: Check if mysqld is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is already active.
) else (
    echo [*] Starting MySQL Server background daemon...
    start /B mysqld --datadir="%USERPROFILE%\mysql-data"
    timeout /t 2 /nobreak >nul
)

echo.
echo ========================================================
echo   Application running at: http://localhost:8000/
echo   Press Ctrl+C to stop the PHP server.
echo ========================================================
set PHP_CLI_SERVER_WORKERS=4
php -S localhost:8000
