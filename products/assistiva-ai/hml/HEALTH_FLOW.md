# Saúde e sincronização — 0.5.0

## Estado e ativação

O código implementa login Google validado no servidor, cadastro de responsável legal/criança, autorização explícita da titularidade, vínculo por código de uso único, envio autenticado de resumo e histórico privado com protocolo de recebimento. Não há associação automática ao Lucas nem compartilhamento com profissionais.

Para ativar o login, criar/configurar um cliente OAuth **Web** no Google Cloud com origem JavaScript `https://lucas.hml.vitrineiapro.com.br`, publicar/configurar a tela de consentimento e os usuários de teste conforme o estado do projeto Google. Definir somente o ID público `GOOGLE_CLIENT_ID` no runtime `.env.runtime` e recriar o serviço `api`. Não é necessário client secret para Google Identity Services com ID token. Sem essa configuração, a API rejeita autenticação (503) e nenhum envio pode ser autorizado.

## Uso

1. Aplicativo → Saúde → Entrar com Google na HML.
2. Responsável entra com Google e cadastra seu nome, vínculo e nome da criança.
3. Confere a titularidade dos registros no Conexão Saúde. Se os dados forem de um adulto ou outra criança, não autoriza para a criança selecionada.
4. Autoriza passos, batimentos e sono, gera código de 12 caracteres (10 minutos) e digita no aplicativo. O vínculo dura 30 dias.
5. No aplicativo, autoriza leitura dos tipos desejados, toca Ler dados agora, confere a criança e confirma o envio manual.
6. Aplicativo exibe protocolo somente após confirmação de gravação pela API. HML → Histórico recebido apresenta dados, origem, horários e protocolo. Reenvio do mesmo lote não duplica; lotes com novas leituras são novos eventos de resumo, não um histórico de registros brutos deduplicados.
7. Revogar na HML bloqueia novos envios e invalida todos os códigos e aparelhos desta criança. Não apaga o histórico já recebido.

## Dados e persistência

Envio de resumo: agregação dos passos de hoje com origem `health_connect.aggregate`, último batimento disponível e última sessão de sono dos últimos 7 dias. Ausências/permissões negadas não viram valores zero. Sono usa duração da sessão, incluindo possíveis períodos acordado.

SQLite e chave Fernet persistem no volume Docker `health-data`; chave criada no servidor com modo 0600. Cadastros, consentimento e payloads são criptografados. IDs, horários de recebimento, contagens e eventos de auditoria são metadados. Backup/restauração devem preservar a chave junto com o banco; não remover o volume ao atualizar. Esta versão não implementa exclusão de histórico, acesso de profissionais ou monitoramento automático.

Cookies de sessão Secure/HttpOnly/SameSite com uma hora, verificação de origem e CSRF para mutações, tokens de aparelho armazenados como SHA-256 no servidor. App usa Android Keystore para criptografar vínculo, último recibo e lote pendente. Backup Android desabilitado. Logs de acesso de `/api` desabilitados no nginx e uvicorn. API não expõe porta de host.

## Validação

`python -m pytest -q hml/api/test_app.py` usa dados sintéticos e OAuth simulado apenas nos testes. Verifica isolamento entre responsáveis, ausência de sessão, CSRF/origem, falta de consentimento, propriedade declarada, uso único de código, upload sem token, recibo, idempotência, conflito de lote, armazenamento criptografado e revogação. Não comprova login real Google nem leitura de aparelho físico: esses exigem cliente OAuth configurado e validação no celular.
