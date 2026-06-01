@echo off
cd /d "%~dp0"
set "PATH=C:\Program Files\nodejs;%PATH%"

if not exist node_modules (
  echo Preparazione iniziale del gestionale...
  call npm install --cache .npm-cache --no-audit --no-fund
)

echo Avvio OsteoCare...
start "" cmd /c "timeout /t 6 /nobreak >nul && start http://localhost:3000"
call npm run dev

pause
