@echo off
set MANIFEST_URL=https://demo.vitrineiapro.com.br/manifest.json
echo Instalando Bubblewrap...
call npm install -g @bubblewrap/cli
echo Inicializando TWA para %MANIFEST_URL%
call bubblewrap init --manifest=%MANIFEST_URL%
echo Gerando APK/AAB...
call bubblewrap build
pause
