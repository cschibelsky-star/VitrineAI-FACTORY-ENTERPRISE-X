#!/usr/bin/env bash
set -e
echo "Gerando projeto TWA do Sumaré Turismo. Use os dados do README quando solicitado."
bubblewrap init --manifest https://demo.vitrineiapro.com.br/manifest.json
