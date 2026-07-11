#!/usr/bin/env bash
set -Eeuo pipefail

STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$HOME/backups-leads/$STAMP"
mkdir -p "$BACKUP_DIR"

printf '\nAUDITORIA DE LEADS — CONHEÇA SUMARÉ\n'
printf 'Data: %s\n' "$(date '+%d/%m/%Y %H:%M:%S %Z')"
printf 'Backup: %s\n\n' "$BACKUP_DIR"

mapfile -t FILES < <(
  find "$HOME" -type f \
    \( -name 'leads_empresas.json' -o -iname '*leads*.json' -o -iname '*solicitacoes*.json' \) \
    -not -path '*/.git/*' \
    -not -path '*/node_modules/*' \
    -not -path '*/vendor/*' \
    -not -path "$HOME/backups-leads/*" \
    2>/dev/null | sort -u
)

if [[ ${#FILES[@]} -eq 0 ]]; then
  echo 'Nenhum arquivo potencial de leads foi localizado no diretório da conta.'
  exit 0
fi

TOTAL_REGISTROS=0
TOTAL_VALIDOS=0

for FILE in "${FILES[@]}"; do
  SAFE_NAME="$(printf '%s' "$FILE" | sed 's#^/##; s#[/ ]#_#g')"
  DEST="$BACKUP_DIR/$SAFE_NAME"
  cp -p "$FILE" "$DEST"

  RESULT="$(php -r '
    $file=$argv[1];
    $raw=@file_get_contents($file);
    if($raw===false){echo "ERRO_LEITURA|0"; exit;}
    $data=json_decode($raw,true);
    if(json_last_error()!==JSON_ERROR_NONE){echo "JSON_INVALIDO|0"; exit;}
    if(!is_array($data)){echo "FORMATO_INVALIDO|0"; exit;}
    echo "OK|".count($data);
  ' "$FILE")"

  STATUS="${RESULT%%|*}"
  COUNT="${RESULT##*|}"
  SIZE="$(stat -c '%s' "$FILE" 2>/dev/null || echo 0)"
  MODIFIED="$(stat -c '%y' "$FILE" 2>/dev/null | cut -d'.' -f1 || echo 'não identificado')"
  WRITABLE='Não'
  [[ -w "$FILE" ]] && WRITABLE='Sim'
  SHA="$(sha256sum "$FILE" | awk '{print $1}')"

  printf '%s\n' '------------------------------------------------------------'
  printf 'Arquivo: %s\n' "$FILE"
  printf 'Status: %s\n' "$STATUS"
  printf 'Registros: %s\n' "$COUNT"
  printf 'Tamanho: %s bytes\n' "$SIZE"
  printf 'Última alteração: %s\n' "$MODIFIED"
  printf 'Gravável pelo usuário atual: %s\n' "$WRITABLE"
  printf 'SHA-256: %s\n' "$SHA"
  printf 'Cópia de segurança: %s\n' "$DEST"

  if [[ "$STATUS" == 'OK' ]]; then
    TOTAL_VALIDOS=$((TOTAL_VALIDOS + 1))
    TOTAL_REGISTROS=$((TOTAL_REGISTROS + COUNT))
  fi
done

printf '%s\n' '============================================================'
printf 'Arquivos encontrados: %s\n' "${#FILES[@]}"
printf 'Arquivos JSON válidos: %s\n' "$TOTAL_VALIDOS"
printf 'Soma dos registros encontrados: %s\n' "$TOTAL_REGISTROS"
printf 'Backups preservados em: %s\n' "$BACKUP_DIR"
printf '%s\n' 'Nenhum arquivo original foi alterado.'
