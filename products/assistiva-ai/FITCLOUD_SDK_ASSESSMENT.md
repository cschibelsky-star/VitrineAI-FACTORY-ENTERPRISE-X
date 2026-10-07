# Avaliação do SDK público FitCloudPro para Projeto Lucas
Data: 2026-10-07
Estado: descoberta BLE e enumeração GATT validadas por capturas do teste físico; autenticação SDK e leitura direta de saúde pendentes.
Este documento contém somente informações públicas do SDK e estado do código do projeto, sem dados pessoais ou identificadores de dispositivos.

## Fontes
- https://github.com/htangsmart/FitCloudPro-SDK-Android
- https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/document/sdk-fitcloud/com.topstep.fitcloud.sdk.v2/-fc-connector/connect.html
- https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/sample/app/src/main/java/com/topstep/fitcloud/sample2/data/device/DeviceManager.kt
- https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/sample/app/src/main/java/com/topstep/fitcloud/sample2/data/device/SyncDataRepository.kt

## Verificações executadas
README inspecionado anuncia 3.0.2.7, publicado em 17/09/2026.
HEAD e GET do POM sdk-fitcloud 3.0.2.7 no servidor HTTPS responderam HTTP 200.
https://maven.topstepht.com/repository/maven-public/com/topstep/wearkit/sdk-fitcloud/3.0.2.7/sdk-fitcloud-3.0.2.7.pom
O POM confirma packaging aar e sdk-base 3.0.2.7.
Isso não valida resolução completa das dependências ou compilação Android.
A avaliação inicial não continha leitura de saúde. O código atual possui leitura Health Connect e diagnóstico BLE/GATT; ainda não possui coleta de saúde direta pelo SDK FitCloudPro.
Não copiar credenciais nem configuração completa do exemplo de terceiros.

## Autenticação: impedimento para teste sem perda de histórico
A documentação de FcConnector.connect declara:
- BIND (bindOrLogin=true) limpa dados anteriores do dispositivo.
- LOGIN (bindOrLogin=false) exige a identidade do último usuário autenticado; divergência gera FcAuthException.
Sem conhecer um mecanismo autorizado de autenticação preservando o histórico, não iniciar vinculação.
Diagnóstico não deve chamar BIND, unbindUser, removeBond, deviceReset, atualização de firmware ou mudanças de configuração.
Descoberta BLE não comprova autenticação nem leitura de dados.

## APIs públicas verificadas
FcConnector.dataFeature().syncData() emite FcSyncData.
O exemplo usa toStep(), toSleep(), toHeartRate(), toOxygen() e toTodayTotal().
Suporte depende das capacidades do dispositivo; tipo existente no SDK não garante disponibilidade.
No exemplo, FcTodayTotalData.distance usa metros e calorie usa calorias.
Metadados de origem, tempo, usuário e dispositivo devem acompanhar registros; não usar dados simulados como reais.

## Critérios para a próxima etapa
1. Verificar termos/licença do SDK e resolver dependências por HTTPS.
2. Preparar diagnóstico BLE com permissões Android, tempo máximo e cancelamento.
3. Resolver autenticação que preserve histórico; não extrair credenciais de outro app.
4. Compilar APK de homologação e validar em hardware físico.
5. Somente após leitura comprovada implementar persistência e envio autenticado ao HML com autorização do responsável.

## Limites
As versões de diagnóstico foram compiladas pelo CI. Em 07/10, capturas fornecidas pelo responsável mostram descoberta de C26 e mapa GATT.
Isso comprova descoberta e enumeração estrutural, não autenticação FitCloudPro nem leitura direta de sensores.
Nenhum pareamento, deploy, migration, merge ou DNS alterado.

## Verificação adicional: conta por e-mail
O exemplo público AuthManagerImpl não autentica na nuvem do FitCloudPro.
signIn e signUp são explicitamente mock: consultam/criam usuários no banco local do exemplo.
A identidade passada ao relógio é user.id.toString(), não o nome de login.
Isso não prova como o aplicativo comercial representa sua conta; não assumir que e-mail equivale a userId.
Não solicitar senha nem reutilizar o mock como autenticação de produção.

Fonte:
https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/sample/app/src/main/java/com/topstep/fitcloud/sample2/data/auth/AuthManager.kt

FcAuthMode é um alias de AuthMode. AUTO é descrito apenas como gerenciamento automático do estado do usuário.
A documentação consultada não garante preservação de histórico quando AUTO é usado com identidade diferente.
Portanto AUTO não deve ser usado como fallback de LOGIN nem para contornar autenticação.
A investigação não identificou uma API pública documentada para converter o login comercial em identidade Bluetooth.
Próximo requisito: obter documentação do fornecedor sobre interoperabilidade com conta existente ou validar a exportação pelo Saúde Connect.
Sem isso, a leitura direta preservando o pareamento permanece bloqueada tecnicamente.

## Implementação de diagnóstico — 06/10/2026
A aba Relógio usa apenas descoberta BLE do Android, sem inicializar FcSDK.
Busca limitada a 20 segundos, cancelável, interrompida ao sair da tela ou colocar app em segundo plano.
Permissões próximas no Android 12+; localização somente no Android 11.
Mostra nome anunciado, RSSI, identificador parcial e UUIDs anunciados.
Resultados mantidos somente em memória; não enviados à HML.
Selecionar um anúncio não autentica, não conecta e não comprova compatibilidade.
Firmware, capacidades dos sensores e medições continuam pendentes.
Não há BIND, LOGIN, AUTO, remoção de pareamento, reset ou atualização de firmware.
O uso de neverForLocation pode filtrar certos anúncios; ausência de resultado não comprova incompatibilidade.
A autenticação preservando histórico permanece o requisito para integrar a coleta FitCloudPro.

### Validação física necessária
- Abrir Relógio, autorizar permissões e Buscar relógio com Bluetooth ativo.
- Android 11: verificar localização ativa.
- Comparar nome com FitCloudPro; anúncios sem nome também podem aparecer.
- Testar Cancelar, negar permissão, desligar Bluetooth e sair do app durante busca.
- Busca vazia: não restaurar nem desvincular C26; ele pode não anunciar enquanto conectado ao FitCloudPro.
- Não registrar dados como pertencentes ao Lucas antes da confirmação do responsável.


## Resultado físico e confronto com SDK — 07/10/2026

Evidência: capturas fornecidas pelo responsável, exibindo C26 e mapa GATT.
Não se armazena aqui endereço Bluetooth, conta ou medidas pessoais.
A captura do mapa não exibe no mesmo quadro o dispositivo selecionado; a atribuição ao C26 segue a sequência do teste solicitado, sem substituir a necessidade de vincular a próxima saída ao dispositivo inspecionado.

### Mapa observado
| Serviço (UUID completo) | Características observadas | Interpretação comprovada |
| --- | --- | --- |
| 00001801-0000-1000-8000-00805f9b34fb | 2A05, 2B3A | Serviço listado; nenhum valor foi lido |
| 00001800-0000-1000-8000-00805f9b34fb | 2A00, 2A01, 2A04, 2AA6 | Serviço listado; nenhum valor foi lido |
| 000001ff-3c17-d293-8e48-14fe2e4da212 | FF02 WRITE; FF03 READ/NOTIFY/WRITE_NO_RESPONSE; FF04 READ/WRITE | Canal proprietário observado; função de saúde não estabelecida |
| 0000d0ff-3c17-d293-8e48-14fe2e4da212 | FFD1 WRITE_NO_RESPONSE; FFD2/FFD3/FFD4/FFF1/FFE0/FFE1/FFF3/FFF4/FFF5 READ | Campos proprietários; não assumir firmware, bateria ou sensor sem documentação |
| 00006287-3c17-d293-8e48-14fe2e4da212 | 00006387-3c17-d293-8e48-14fe2e4da212 WRITE_NO_RESPONSE; 00006487-3c17-d293-8e48-14fe2e4da212 NOTIFY/WRITE | Canal proprietário; não enviar comandos experimentais |
| 0000fee7-0000-1000-8000-00805f9b34fb | FEC9 READ; FEA1 READ/NOTIFY | Serviço anunciado e descoberto; sem prova de transporte FitCloudPro |
| 00001812-0000-1000-8000-00805f9b34fb | 2A4E, 2A4D (múltiplas), 2A4B, 2A22, 2A32, 2A4A, 2A4C | Serviço listado; READ/NOTIFY/WRITE descrevem permissões, não conteúdo de saúde |

As abreviações de características de 16 bits na tabela usam a base 0000XXXX-0000-1000-8000-00805f9b34fb.
O serviço de frequência cardíaca 180D não aparece nas capturas recebidas. Isso não comprova ausência do sensor físico nem ausência em uma captura incompleta.
Não existe nas fontes públicas consultadas um mapeamento comprovado de FF02/FF03 para uma operação específica de batimentos no C26.
Não atribuir bytes desses campos a bpm, passos, sono ou SpO2 por tentativa.

### O que o SDK confirma
Releitura em 07/10:
- FcConnector.connect requer userId para LOGIN ou BIND.
- BIND limpa dados anteriores do dispositivo, incluindo passos, sono e batimentos.
- LOGIN compara userId ao usuário anteriormente autenticado.
- O exemplo DeviceManager usa user.id.toString(); AuthManager é mock de banco local.
- O exemplo separa HEART_RATE e HEART_RATE_MEASURE e usa conversores distintos.
- A enumeração GATT nativa não resolve nenhuma dessas condições.

### Decisão de implementação
Manter o diagnóstico existente, sem adicionar dependência SDK, BIND/AUTO, tentativas de userId, comandos proprietários, notificações ou leituras especulativas.
A autorização para comunicação preservando vínculo e histórico não equivale a autorização para recriar vínculo.
A coleta direta continua tecnicamente bloqueada até obter uma identidade de LOGIN compatível por mecanismo documentado e autorizado pelo fornecedor.
Nenhuma nova versão de APK para coleta deve ser apresentada como funcional enquanto esse requisito não for satisfeito.

### Requisitos objetivos para o fornecedor
1. Como integrar com o dispositivo já vinculado ao aplicativo comercial sem BIND e sem apagar histórico?
2. Existe API pública/fluxo de consentimento que obtenha o userId e authCode corretos para essa conta existente?
3. Quais serviços/versões de protocolo do C26 são suportados pelo sdk-fitcloud 3.0.2.7?
4. Quais comandos de syncData alteram ou removem registros do relógio, e como preservar o histórico?
5. Quais tipos (HEART_RATE versus HEART_RATE_MEASURE, passos, sono) são exportados para Health Connect ou Google Fit, e em qual momento?

Não solicitar senha da conta, extrair armazenamento do aplicativo comercial nem enviar dados pessoais ao fornecedor sem autorização.
Enquanto isso, a rota Health Connect já existente pode consumir os registros que forem efetivamente exportados; ela não oferece medições ao vivo nem garante atualização de todos os tipos.

### Fontes verificadas
- FcConnector.connect: https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/document/sdk-fitcloud/com.topstep.fitcloud.sdk.v2/-fc-connector/connect.html
- DeviceManager: https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/sample/app/src/main/java/com/topstep/fitcloud/sample2/data/device/DeviceManager.kt
- AuthManager: https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/sample/app/src/main/java/com/topstep/fitcloud/sample2/data/auth/AuthManager.kt
- Versão publicada: https://github.com/htangsmart/FitCloudPro-SDK-Android/blob/master/README.md


## Mudança de escopo autorizada — 07/10/2026
O responsável confirmou que o histórico atual é somente teste e autorizou nova vinculação, incluindo possível perda desses registros.
O impedimento de preservar o vínculo comercial permanece para uma futura migração de dados reais, mas não bloqueia este teste isolado autorizado.

### APK 0.9.0: teste pelo SDK
- Dependências sdk-base e sdk-fitcloud 3.0.2.7 pelo repositório HTTPS do fornecedor.
- SDK inicializado sob demanda; não conectar nem vincular no início do aplicativo.
- Identidade aleatória de 24 caracteres guardada localmente antes do primeiro comando.
- BIND somente após seleção de C26, correspondência do endereço completo, perfil explícito e confirmação no dispositivo.
- LOGIN somente com endereço cujo BIND foi confirmado como CONNECTED e guardado localmente.
- Não alternar automaticamente LOGIN para BIND em caso de erro.
- Timeout de conexão de 60 segundos; sincronização limitada a 90 segundos sem emissão.
- Sessão encerrada ao sair da tela/app; nenhuma coleta em segundo plano.
- Sem controles de música/telefonia/idioma nem ajuste automático de hora; o SDK transmite parâmetros de perfil exigidos pela conexão.
- Receber passos, totais, batimentos históricos/manuais e registros de sono via conversores oficiais.
- Sono apresentado como segmentos; não inferir duração nem crises.
- Registros de teste somente em memória, máximo 1.000; tela mostra os 30 mais recentes com timestamp e origem SDK.
- Sem atribuição ao Lucas, upload HML, reset, unbind, removeBond ou OTA.
- Não desinstalar após vínculo confirmado sem planejar a perda da identidade local.

### Verificação física após compilação
1. Instalar e verificar versão 0.9.0 na área de leitura SDK.
2. Forçar parada do FitCloudPro, manter Bluetooth ligado e aproximar C26.
3. Informar endereço completo localmente, buscar e selecionar somente C26 correspondente.
4. Preencher perfil da pessoa que usa relógio e confirmar nova vinculação de teste.
5. Esperar confirmação C26 autenticado; sincronizar.
6. Após apagar histórico no BIND, pode haver zero registros. Gerar novos dados no relógio e sincronizar novamente.
7. Encerrar conexão, reabrir e reconectar pelo LOGIN; verificar que nova vinculação não ocorre.
8. Sair durante conexão/sync, revogar permissão e desligar Bluetooth: sessão deve terminar e informar falha.
9. Não declarar coleta validada até receber e comparar dados reais no hardware.
