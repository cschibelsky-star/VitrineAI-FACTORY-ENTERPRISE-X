SUMARÉ TURISMO — TWA ANDROID 3.0 TESTE
======================================

Objetivo:
Preparar o PWA hospedado em https://demo.vitrineiapro.com.br para virar APK Android via Trusted Web Activity (TWA).

STATUS DESTA BUILD
------------------
- PWA web preservado.
- Pasta .well-known/assetlinks.json incluída.
- Keystore de TESTE gerada.
- SHA-256 calculado e aplicado no assetlinks.json.
- Scripts de apoio para gerar projeto Android com Bubblewrap incluídos.

DADOS DO APP ANDROID
--------------------
Nome: Sumaré Turismo
Domínio: demo.vitrineiapro.com.br
Package name: br.com.vitrineiapro.visitesumare
Manifest: https://demo.vitrineiapro.com.br/manifest.json
SHA-256 de teste: E5:40:EE:63:3D:7B:6E:67:DE:16:76:02:93:96:C2:6A:A8:D8:2E:51:4D:97:5D:E4:77:E6:09:67:AC:9C:86:9F

IMPORTANTE
----------
A keystore incluída é para TESTE. Não use essa senha/keystore como chave definitiva de Play Store sem antes decidir formalmente.
Se gerar outra chave, será obrigatório trocar o SHA-256 dentro de /.well-known/assetlinks.json e subir novamente na HostGator.

PASSO 1 — SUBIR ESTA BUILD NA HOSTGATOR
---------------------------------------
Suba o conteúdo do ZIP no mesmo local do app atual.
Confirme que estes endereços abrem no navegador:

https://demo.vitrineiapro.com.br/manifest.json
https://demo.vitrineiapro.com.br/.well-known/assetlinks.json

PASSO 2 — INSTALAR AMBIENTE LOCAL
---------------------------------
No computador que vai gerar APK, instalar:
- Node.js LTS
- Android Studio
- Java/JDK
- Bubblewrap CLI

Comando:
 npm install -g @bubblewrap/cli
 bubblewrap doctor

PASSO 3 — GERAR PROJETO TWA
---------------------------
Use:
 bubblewrap init --manifest https://demo.vitrineiapro.com.br/manifest.json

Quando o Bubblewrap perguntar os dados, usar:
Package ID: br.com.vitrineiapro.visitesumare
App name: Sumaré Turismo
Short name: Sumaré Turismo
Host: demo.vitrineiapro.com.br
Start URL: /index.php
Display mode: standalone
Orientation: portrait
Theme color: #0F6B3A

PASSO 4 — USAR A KEYSTORE DE TESTE
----------------------------------
Keystore:
 android-twa/keystore/visite-sumare-twa-teste.keystore
Alias:
 visitesumare
Senha:
 VisiteSumare2026

PASSO 5 — GERAR APK
-------------------
Dentro da pasta do projeto TWA gerado:
 bubblewrap build

O APK de teste será gerado na saída indicada pelo Bubblewrap.

PASSO 6 — TESTAR NO ANDROID
---------------------------
Instalar o APK manualmente no celular.
Validar:
- abertura em tela cheia;
- sem barra do navegador;
- ícone correto;
- Home;
- Atrativos;
- Eventos;
- Guia Comercial;
- Mapa;
- Perfil.

OBSERVAÇÃO SOBRE TELA CHEIA
---------------------------
Para a TWA abrir sem barra do navegador, o Digital Asset Links precisa validar corretamente.
Se aparecer barra ou abrir como Custom Tab, normalmente o problema é SHA-256 diferente, package name diferente ou assetlinks.json inacessível.
