@echo off
setlocal
rem Double-click helper: runs push.ps1 with any arguments you pass.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0push.ps1" %*
echo.
pause
