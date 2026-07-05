@echo off
echo Instalando Bubblewrap CLI...
npm install -g @bubblewrap/cli
echo.
echo Verificando ambiente Android...
bubblewrap doctor
pause
