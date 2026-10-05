package br.com.vitrineaipro.assistiva.tablet

import android.content.Intent
import android.net.Uri
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.health.connect.client.HealthConnectClient
import androidx.health.connect.client.PermissionController
import androidx.health.connect.client.permission.HealthPermission
import androidx.health.connect.client.records.HeartRateRecord
import androidx.health.connect.client.records.SleepSessionRecord
import androidx.health.connect.client.records.StepsRecord
import androidx.health.connect.client.request.AggregateRequest
import androidx.health.connect.client.request.ReadRecordsRequest
import androidx.health.connect.client.time.TimeRangeFilter
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.TimeoutCancellationException
import kotlinx.coroutines.launch
import kotlinx.coroutines.withTimeout
import java.time.Duration
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId
import java.time.format.DateTimeFormatter

private val permissions = setOf(
    HealthPermission.getReadPermission(StepsRecord::class),
    HealthPermission.getReadPermission(HeartRateRecord::class),
    HealthPermission.getReadPermission(SleepSessionRecord::class)
)
private val timeFormat = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm")
private fun date(time: Instant) = timeFormat.format(time.atZone(ZoneId.systemDefault()))
private fun source(packageName: String) =
    if (packageName == "com.google.android.apps.fitness") "Google Fit" else packageName
private data class HealthResult(val title: String, val value: String, val detail: String)

@Composable
fun HealthScreen() {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var sdk by remember { mutableIntStateOf(HealthConnectClient.getSdkStatus(context)) }
    var granted by remember { mutableStateOf<Set<String>>(emptySet()) }
    var busy by remember { mutableStateOf(false) }
    var message by remember { mutableStateOf("Conecte para autorizar a leitura neste celular.") }
    var results by remember { mutableStateOf<List<HealthResult>>(emptyList()) }
    var lastRead by remember { mutableStateOf<Instant?>(null) }
    val client = remember(sdk) {
        if (sdk == HealthConnectClient.SDK_AVAILABLE) HealthConnectClient.getOrCreate(context) else null
    }

    suspend fun refreshPermissions() {
        sdk = HealthConnectClient.getSdkStatus(context)
        val available = client ?: return
        try {
            val current = withTimeout(10_000) { available.permissionController.getGrantedPermissions() }
            if (current != granted) {
                results = emptyList()
                lastRead = null
            }
            granted = current.intersect(permissions)
        } catch (cancel: CancellationException) {
            if (cancel is TimeoutCancellationException) message = "Demorou para verificar as permissões. Tente novamente."
            else throw cancel
        } catch (_: Exception) {
            granted = emptySet()
            results = emptyList()
            lastRead = null
            message = "Não foi possível verificar as permissões. Abra o Conexão Saúde e tente novamente."
        }
    }

    fun read() {
        if (busy || client == null) return
        scope.launch {
            busy = true
            results = emptyList()
            lastRead = null
            message = "Lendo registros do Conexão Saúde…"
            try {
                withTimeout(30_000) {
                    granted = client.permissionController.getGrantedPermissions().intersect(permissions)
                    if (granted.isEmpty()) {
                        message = "Nenhuma leitura autorizada. Toque em Conectar."
                        return@withTimeout
                    }
                    val now = Instant.now()
                    val start = now.minus(Duration.ofDays(7))
                    val range = TimeRangeFilter.between(start, now)
                    val output = mutableListOf<HealthResult>()
                    if (HealthPermission.getReadPermission(StepsRecord::class) in granted) {
                        val today = LocalDate.now().atStartOfDay(ZoneId.systemDefault()).toInstant()
                        val total = client.aggregate(AggregateRequest(
                            metrics = setOf(StepsRecord.COUNT_TOTAL),
                            timeRangeFilter = TimeRangeFilter.between(today, now)
                        ))[StepsRecord.COUNT_TOTAL]
                        val latest = client.readRecords(ReadRecordsRequest(
                            recordType = StepsRecord::class, timeRangeFilter = range,
                            ascendingOrder = false, pageSize = 1
                        )).records.firstOrNull()
                        output += HealthResult("Passos hoje", total?.toString() ?: "Sem dados",
                            latest?.let { "Último registro: ${date(it.endTime)}\nOrigem: ${source(it.metadata.dataOrigin.packageName)}\nO total usa a agregação do Conexão Saúde." }
                                ?: "Nenhum registro encontrado nos últimos 7 dias.")
                    } else output += HealthResult("Passos", "Sem permissão", "Autorize a leitura em Conectar.")

                    if (HealthPermission.getReadPermission(HeartRateRecord::class) in granted) {
                        // Read all pages, then choose by sample time, not by record start time.
                        var token: String? = null
                        var latestTime: Instant? = null
                        var latestBpm: Long? = null
                        var latestOrigin: String? = null
                        do {
                            val page = client.readRecords(ReadRecordsRequest(
                                recordType = HeartRateRecord::class, timeRangeFilter = range,
                                ascendingOrder = false, pageSize = 1000, pageToken = token
                            ))
                            for (record in page.records) {
                                for (sample in record.samples) {
                                    if (sample.time >= start && sample.time <= now &&
                                        (latestTime == null || sample.time > latestTime)) {
                                        latestTime = sample.time
                                        latestBpm = sample.beatsPerMinute
                                        latestOrigin = record.metadata.dataOrigin.packageName
                                    }
                                }
                            }
                            token = page.pageToken
                        } while (token != null)
                        output += HealthResult("Último batimento", latestBpm?.let { "$it bpm" } ?: "Sem dados",
                            latestTime?.let { "${date(it)}\nOrigem: ${source(latestOrigin.orEmpty())}" }
                                ?: "Nenhum registro encontrado nos últimos 7 dias.")
                    } else output += HealthResult("Batimentos", "Sem permissão", "Autorize a leitura em Conectar.")

                    if (HealthPermission.getReadPermission(SleepSessionRecord::class) in granted) {
                        var token: String? = null
                        var latest: SleepSessionRecord? = null
                        do {
                            val page = client.readRecords(ReadRecordsRequest(
                                recordType = SleepSessionRecord::class, timeRangeFilter = range,
                                ascendingOrder = false, pageSize = 1000, pageToken = token
                            ))
                            for (record in page.records) {
                                if (latest == null || record.endTime > latest.endTime) latest = record
                            }
                            token = page.pageToken
                        } while (token != null)
                        val session = latest
                        val minutes = session?.let { Duration.between(it.startTime, it.endTime).toMinutes() }
                        output += HealthResult("Última sessão de sono",
                            minutes?.let { "${it / 60}h ${it % 60}min" } ?: "Sem dados",
                            session?.let { "${date(it.startTime)} → ${date(it.endTime)}\nOrigem: ${source(it.metadata.dataOrigin.packageName)}\nDuração da sessão registrada; pode incluir períodos acordado." }
                                ?: "Nenhum registro encontrado nos últimos 7 dias.")
                    } else output += HealthResult("Sono", "Sem permissão", "Autorize a leitura em Conectar.")
                    results = output
                    lastRead = Instant.now()
                    message = "Leitura concluída. Permissões: ${granted.size}/3."
                }
            } catch (_: TimeoutCancellationException) {
                message = "A leitura demorou mais de 30 segundos. Tente novamente."
            } catch (cancel: CancellationException) {
                throw cancel
            } catch (_: SecurityException) {
                granted = emptySet()
                results = emptyList()
                message = "Permissão retirada ou acesso bloqueado. Reconecte ao Conexão Saúde."
            } catch (_: Exception) {
                message = "Não foi possível ler os registros. Verifique o Conexão Saúde e tente novamente."
            } finally {
                busy = false
            }
        }
    }

    val launcher = rememberLauncherForActivityResult(
        PermissionController.createRequestPermissionResultContract()
    ) { allowed ->
        granted = allowed.intersect(permissions)
        results = emptyList()
        lastRead = null
        if (granted.isNotEmpty()) read()
        else message = "A leitura não foi autorizada. Você pode tentar novamente."
    }
    LaunchedEffect(client) { refreshPermissions() }
    DisposableEffect(context, client) {
        val lifecycle = (context as? ComponentActivity)?.lifecycle
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME && !busy) {
                scope.launch { refreshPermissions() }
            }
        }
        lifecycle?.addObserver(observer)
        onDispose { lifecycle?.removeObserver(observer) }
    }

    Column(
        Modifier.fillMaxWidth().verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Text("Saúde", style = MaterialTheme.typography.headlineSmall)
        Text("Dados deste celular", style = MaterialTheme.typography.titleMedium)
        Text("Confira de quem são os registros. Eles não são vinculados automaticamente ao Lucas.")
        Card(Modifier.fillMaxWidth()) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text(when (sdk) {
                    HealthConnectClient.SDK_AVAILABLE -> "Conexão Saúde disponível"
                    HealthConnectClient.SDK_UNAVAILABLE_PROVIDER_UPDATE_REQUIRED -> "Instale ou atualize o Conexão Saúde"
                    else -> "Conexão Saúde indisponível neste aparelho"
                }, style = MaterialTheme.typography.titleMedium)
                Text(message)
                Text("Última leitura: ${lastRead?.let { date(it) } ?: "nenhuma"}")
                Text("Leitura manual · últimos 7 dias · sem coleta em segundo plano")
            }
        }
        if (sdk == HealthConnectClient.SDK_AVAILABLE) {
            Button(
                enabled = !busy,
                onClick = {
                    try { launcher.launch(permissions) }
                    catch (_: Exception) { message = "Não foi possível abrir as permissões. Abra o Conexão Saúde nas configurações." }
                }, modifier = Modifier.fillMaxWidth()
            ) { Text("Conectar / permissões") }
            OutlinedButton(enabled = !busy && granted.isNotEmpty(), onClick = { read() },
                modifier = Modifier.fillMaxWidth()) { Text("Ler dados agora") }
        } else if (sdk == HealthConnectClient.SDK_UNAVAILABLE_PROVIDER_UPDATE_REQUIRED) {
            Button(onClick = {
                try {
                    context.startActivity(Intent(Intent.ACTION_VIEW,
                        Uri.parse("https://play.google.com/store/apps/details?id=com.google.android.apps.healthdata")))
                } catch (_: Exception) { message = "Procure Conexão Saúde na Play Store." }
            }) { Text("Instalar / atualizar") }
        }
        if (busy) LinearProgressIndicator(Modifier.fillMaxWidth())
        results.forEach { result ->
            OutlinedCard(Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(result.title, style = MaterialTheme.typography.titleMedium)
                    Text(result.value, style = MaterialTheme.typography.headlineSmall)
                    Text(result.detail)
                }
            }
        }
        Text("Privacidade", style = MaterialTheme.typography.titleMedium)
        Text("Os dados ficam apenas na memória desta tela. Não alteramos nem apagamos o histórico do Conexão Saúde. Você pode revogar as permissões nas configurações do Android.")
        Text("Envio à HML: não configurado. Nenhum dado de saúde é enviado nesta versão.")
        Text("Esses registros não são monitoramento em tempo real.")
        OutlinedButton(onClick = { context.startActivity(Intent(context, HealthPrivacyActivity::class.java)) }) {
            Text("Como usamos seus dados")
        }
        Spacer(Modifier.height(16.dp))
    }
}
