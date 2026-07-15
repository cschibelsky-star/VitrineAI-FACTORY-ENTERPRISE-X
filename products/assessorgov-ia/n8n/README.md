# Integração n8n — AssessorGov IA

O n8n será usado para automações assíncronas, sem substituir o núcleo transacional da aplicação.

## Workflows iniciais

1. `assessorgov-oportunidades-ingestao`: coleta fontes oficiais e APIs autorizadas, normaliza os registros e envia os dados para a API interna.
2. `assessorgov-alertas-prazos`: consulta oportunidades com prazo próximo, segmenta por cliente e envia alertas.
3. `assessorgov-documentos-analise`: recebe eventos de upload, aciona o serviço de IA e devolve resumo, checklist e riscos.

## Segurança

- usar HTTPS;
- autenticar webhooks com `N8N_WEBHOOK_TOKEN`;
- manter credenciais no cofre do n8n;
- não exportar segredos junto aos workflows;
- usar chave de idempotência para impedir registros duplicados;
- registrar logs sem documentos ou dados pessoais sensíveis.
