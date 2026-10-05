# Avaliação do SDK público FitCloudPro para Projeto Lucas
Data: 2026-10-05
Estado: avaliação de documentação concluída; implementação e teste físico pendentes.
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
O tablet-app atual usa Compose e TTS; não contém leitura FitCloudPro ou Saúde Connect.
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
Nenhum APK produzido ou compilado nesta avaliação.
Nenhuma conexão física ou leitura de sensores realizada.
A compatibilidade de um relógio específico permanece não comprovada.
Nenhum pareamento, deploy, migration, merge ou DNS alterado.
