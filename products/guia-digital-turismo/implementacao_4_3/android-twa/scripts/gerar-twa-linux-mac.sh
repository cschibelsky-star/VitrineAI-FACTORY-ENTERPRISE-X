#!/usr/bin/env bash
set -e
MANIFEST_URL="https://demo.vitrineiapro.com.br/manifest.json"
npm install -g @bubblewrap/cli
bubblewrap init --manifest="$MANIFEST_URL"
bubblewrap build
