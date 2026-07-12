# Plano de recuperação dos leads — Conheça Sumaré

## Objetivo

Localizar, preservar e tornar visíveis as solicitações já enviadas pelo formulário de cadastro empresarial, sem substituir o arquivo de produção.

## Armazenamento confirmado

A implementação 4.3 grava as solicitações em:

`data/leads_empresas.json`

O formulário responsável é:

`cadastro-empresa.php`

A consulta administrativa ocorre em:

`admin/leads.php`

## Regra principal

Nunca substituir o arquivo `data/leads_empresas.json` do site publicado por uma cópia vazia proveniente do repositório.

O arquivo versionado contém apenas `[]` porque dados pessoais e comerciais não devem ser enviados ao GitHub.

## Etapa 1 — localizar e preservar os dados

Na raiz do repositório oficial:

```bash
git fetch origin
git checkout fix/auditoria-leads-conheca-sumare
chmod +x tools/auditar-leads-producao.sh
./tools/auditar-leads-producao.sh
```

O auditor:

- procura arquivos de leads dentro da conta de hospedagem;
- valida se o conteúdo é JSON;
- informa a quantidade de registros;
- mostra data de alteração e permissão de gravação;
- calcula SHA-256;
- cria uma cópia em `~/backups-leads/AAAAmmdd_HHMMSS`;
- não altera nenhum arquivo original.

## Etapa 2 — configurar credencial administrativa segura

A senha antiga foi removida do código. Ela deve ser trocada porque já esteve presente no histórico público do repositório.

Gere um hash:

```bash
php -r "echo password_hash('COLOQUE_AQUI_UMA_SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```

No diretório publicado do Conheça Sumaré, crie:

`config/credentials.local.php`

Conteúdo:

```php
<?php
return [
    'user' => 'admin',
    'password_hash' => 'COLE_AQUI_O_HASH_GERADO',
];
```

Esse arquivo está protegido por `.gitignore` e não deve ser enviado ao GitHub.

## Etapa 3 — implantação sem perda de leads

Antes de copiar a correção, preserve novamente:

```bash
cp -p data/leads_empresas.json "$HOME/leads_empresas_antes_da_correcao_$(date +%Y%m%d_%H%M%S).json"
```

Ao sincronizar os arquivos da implementação para o site publicado, exclua obrigatoriamente:

```text
data/leads_empresas.json
config/credentials.local.php
```

## Etapa 4 — conferência após implantação

Acesse no painel:

- `admin/leads.php`
- `admin/auditoria-leads.php`

Confirme:

- total de registros;
- arquivo gravável;
- ausência de IDs duplicados;
- ausência de registros incompletos;
- exportação CSV funcionando;
- novo envio de teste aparecendo no topo da listagem.

## Etapa 5 — centralização no Comercial Master

Somente depois da recuperação e conferência dos dados locais, implementar sincronização com o Centro Operacional Master. O JSON atual deve permanecer como contingência até a confirmação de que todos os formulários utilizam o endpoint central.
