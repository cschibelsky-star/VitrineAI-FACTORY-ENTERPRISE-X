# Sumaré Turismo — TWA Android no Gatilho

Esta pasta prepara a transformação do PWA hospedado na HostGator em APK/AAB Android via Trusted Web Activity.

## Status atual

- PWA online: https://demo.vitrineiapro.com.br
- Manifest: https://demo.vitrineiapro.com.br/manifest.json
- Package name sugerido para teste: `br.com.vitrineiapro.visitesumare`
- Publicação Play Store: deixar para depois da aprovação do piloto.

## O que ainda impede gerar APK definitivo aqui

O APK/AAB final depende de ambiente Android local ou cloud build com:

- Node.js
- Java JDK
- Android SDK / Android Studio
- Bubblewrap CLI
- Keystore Android
- SHA-256 real da chave

## Comandos base

```bash
npm install -g @bubblewrap/cli
bubblewrap init --manifest=https://demo.vitrineiapro.com.br/manifest.json
bubblewrap build
```

## Digital Asset Links

Depois de criar a keystore e obter o SHA-256, publique:

```text
https://demo.vitrineiapro.com.br/.well-known/assetlinks.json
```

Use o modelo em:

```text
.well-known/assetlinks.MODELO-NAO-RENOMEAR-SEM-SHA.json
```

## Critério de sucesso

O APK deve abrir o Sumaré Turismo em tela cheia, sem barra do navegador. Se abrir com barra do Chrome, o problema quase sempre está no `assetlinks.json` ou no SHA-256.
