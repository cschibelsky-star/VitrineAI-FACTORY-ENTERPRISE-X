package br.com.vitrineaipro.assistiva.tablet

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothManager
import android.bluetooth.le.BluetoothLeScanner
import android.bluetooth.le.ScanCallback
import android.bluetooth.le.ScanResult
import android.bluetooth.le.ScanSettings
import android.content.Context
import android.content.pm.PackageManager
import android.location.LocationManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner

private data class NearbyWatch(
    val address: String,
    val name: String,
    val rssi: Int,
    val services: List<String>
)

private class WatchDiscovery(private val context: Context) {
    var devices by mutableStateOf<List<NearbyWatch>>(emptyList())
        private set
    var scanning by mutableStateOf(false)
        private set
    var status by mutableStateOf("Pronto para buscar dispositivos próximos.")
    private val handler = Handler(Looper.getMainLooper())
    private var scanner: BluetoothLeScanner? = null
    private var activeCallback: ScanCallback? = null
    private val timeout = Runnable { stop("Busca concluída. Selecione o relógio pelo nome exibido no FitCloudPro.") }

    fun permissions(): Array<String> = if (Build.VERSION.SDK_INT >= 31) {
        arrayOf(Manifest.permission.BLUETOOTH_SCAN, Manifest.permission.BLUETOOTH_CONNECT)
    } else arrayOf(Manifest.permission.ACCESS_FINE_LOCATION)

    fun allowed(): Boolean = permissions().all {
        context.checkSelfPermission(it) == PackageManager.PERMISSION_GRANTED
    }

    @SuppressLint("MissingPermission")
    fun start() {
        if (scanning) return
        if (!allowed()) {
            status = "Permissão necessária. Autorize a busca de dispositivos nas configurações do aplicativo."
            return
        }
        val adapter = context.getSystemService(BluetoothManager::class.java)?.adapter
        if (adapter == null || !context.packageManager.hasSystemFeature(PackageManager.FEATURE_BLUETOOTH_LE)) {
            status = "Este aparelho não disponibiliza Bluetooth LE."
            return
        }
        try {
            if (!adapter.isEnabled) {
                status = "Ative o Bluetooth do aparelho e tente novamente."
                return
            }
            if (Build.VERSION.SDK_INT < 31 &&
                context.getSystemService(LocationManager::class.java)?.isLocationEnabled != true) {
                status = "No Android 11, ative a localização do aparelho para permitir a descoberta Bluetooth."
                return
            }
            val currentScanner = adapter.bluetoothLeScanner
            if (currentScanner == null) {
                status = "Bluetooth indisponível. Ative-o e tente novamente."
                return
            }
            devices = emptyList()
            val callback = object : ScanCallback() {
                override fun onScanResult(callbackType: Int, result: ScanResult) {
                    receive(this, result)
                }
                override fun onBatchScanResults(results: MutableList<ScanResult>) {
                    results.forEach { receive(this, it) }
                }
                override fun onScanFailed(errorCode: Int) {
                    handler.post {
                        if (activeCallback === this) stop("Não foi possível buscar dispositivos (código $errorCode). Aguarde e tente novamente.")
                    }
                }
            }
            scanner = currentScanner
            activeCallback = callback
            scanning = true
            status = "Buscando por até 20 segundos…"
            currentScanner.startScan(null, ScanSettings.Builder()
                .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY).build(), callback)
            handler.postDelayed(timeout, 20_000)
        } catch (_: SecurityException) {
            stop("Permissão Bluetooth revogada. Autorize novamente.")
        } catch (_: IllegalStateException) {
            stop("Bluetooth indisponível. Verifique se está ativado.")
        }
    }

    private fun receive(callback: ScanCallback, result: ScanResult) {
        handler.post {
            if (!scanning || activeCallback !== callback) return@post
            val address = result.device.address
            val device = NearbyWatch(
                address,
                result.scanRecord?.deviceName?.take(80) ?: "Dispositivo sem nome",
                result.rssi,
                result.scanRecord?.serviceUuids?.map { it.toString() } ?: emptyList()
            )
            devices = (devices.filterNot { it.address == address } + device)
                .sortedByDescending { it.rssi }.take(50)
        }
    }

    @SuppressLint("MissingPermission")
    fun stop(message: String? = null) {
        val callback = activeCallback
        activeCallback = null
        scanning = false
        handler.removeCallbacks(timeout)
        try {
            if (callback != null) scanner?.stopScan(callback)
        } catch (_: SecurityException) {
            status = "Permissão Bluetooth revogada."
        } catch (_: IllegalStateException) {
            status = "Bluetooth desligado."
        }
        scanner = null
        if (message != null) status = message
    }
}

@Composable
fun WatchDiagnosticScreen() {
    val context = LocalContext.current
    val owner = LocalLifecycleOwner.current
    val discovery = remember { WatchDiscovery(context.applicationContext) }
    var selected by remember { mutableStateOf<NearbyWatch?>(null) }
    val permissions = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) {
        if (discovery.allowed()) discovery.start()
        else discovery.status = "Busca não iniciada: permissão recusada. Você pode autorizá-la nas configurações do aplicativo."
    }
    DisposableEffect(owner, discovery) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_STOP && discovery.scanning) {
                discovery.stop("Busca interrompida ao sair do aplicativo.")
            }
        }
        owner.lifecycle.addObserver(observer)
        onDispose {
            owner.lifecycle.removeObserver(observer)
            discovery.stop()
        }
    }
    LazyColumn(verticalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxSize()) {
        item {
            Text("Diagnóstico do relógio", style = MaterialTheme.typography.titleLarge)
            Text("Aproxime o C26 e compare o nome com o exibido no FitCloudPro. A busca identifica anúncios Bluetooth; não confirma compatibilidade nem coleta saúde.")
            Text("Os resultados ficam apenas nesta tela. Nenhum pareamento ou histórico é alterado.")
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(enabled = !discovery.scanning, onClick = {
                    selected = null
                    if (discovery.allowed()) discovery.start()
                    else permissions.launch(discovery.permissions())
                }) { Text("Buscar relógio") }
                OutlinedButton(enabled = discovery.scanning, onClick = {
                    discovery.stop("Busca cancelada.")
                }) { Text("Cancelar") }
            }
            Text(discovery.status)
            if (discovery.scanning) LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            if (!discovery.scanning && discovery.devices.isEmpty()) {
                Text("Nenhum resultado nesta busca. Isso pode ocorrer se o relógio já estiver conectado e não anunciar por Bluetooth. Não desfaça o pareamento nem restaure o relógio.")
            }
        }
        selected?.let { watch ->
            item {
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Selecionado: ${watch.name}", style = MaterialTheme.typography.titleMedium)
                        Text("Descoberto por Bluetooth; conexão e autenticação ainda não realizadas.")
                        Text("Firmware e sensores: não consultados.")
                        Text("Última medição: nenhuma lida. Envio à HML: não realizado.")
                        Text("Leitura direta depende da autenticação FitCloudPro que preserve o histórico.")
                    }
                }
            }
        }
        items(discovery.devices, key = { it.address }) { device ->
            OutlinedCard(onClick = {
                selected = device
                discovery.stop("Dispositivo selecionado. Nenhuma conexão foi iniciada.")
            }, modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    Text(device.name, style = MaterialTheme.typography.titleMedium)
                    Text("Identificador local: …${device.address.takeLast(5)} • Sinal: ${device.rssi} dBm")
                    Text(if (device.services.isEmpty()) "Nenhum serviço anunciado."
                        else "Serviços anunciados: ${device.services.joinToString()}")
                }
            }
        }
    }
}
