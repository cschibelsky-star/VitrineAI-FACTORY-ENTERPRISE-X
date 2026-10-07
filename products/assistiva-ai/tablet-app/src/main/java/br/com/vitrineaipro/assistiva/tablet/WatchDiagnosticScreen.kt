package br.com.vitrineaipro.assistiva.tablet

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothGatt
import android.bluetooth.BluetoothGattCallback
import android.bluetooth.BluetoothGattCharacteristic
import android.bluetooth.BluetoothGattService
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothProfile
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
import androidx.compose.runtime.saveable.rememberSaveable
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

private data class GattCharacteristicInfo(
    val uuid: String,
    val properties: String
)

private data class GattServiceInfo(
    val uuid: String,
    val characteristics: List<GattCharacteristicInfo>
)

private fun characteristicProperties(characteristic: BluetoothGattCharacteristic): String {
    val properties = characteristic.properties
    val labels = buildList {
        if (properties and BluetoothGattCharacteristic.PROPERTY_READ != 0) add("READ")
        if (properties and BluetoothGattCharacteristic.PROPERTY_NOTIFY != 0) add("NOTIFY")
        if (properties and BluetoothGattCharacteristic.PROPERTY_INDICATE != 0) add("INDICATE")
        if (properties and BluetoothGattCharacteristic.PROPERTY_WRITE != 0) add("WRITE")
        if (properties and BluetoothGattCharacteristic.PROPERTY_WRITE_NO_RESPONSE != 0) add("WRITE_NO_RESPONSE")
    }
    return labels.ifEmpty { listOf("sem propriedade de I/O conhecida") }.joinToString(", ")
}

private fun mapGattServices(services: List<BluetoothGattService>): List<GattServiceInfo> =
    services.map { service ->
        GattServiceInfo(
            uuid = service.uuid.toString(),
            characteristics = service.characteristics.map { characteristic ->
                GattCharacteristicInfo(
                    uuid = characteristic.uuid.toString(),
                    properties = characteristicProperties(characteristic)
                )
            }
        )
    }

private class WatchDiscovery(private val context: Context) {
    var targetAddress by mutableStateOf("0C:CF")
    var searchCompleted by mutableStateOf(false)
        private set
    fun matches(address: String): Boolean = address.endsWith(targetAddress.trim(), ignoreCase = true)
    fun validTarget(): Boolean = Regex("(?i)([0-9a-f]{2}:){1,5}[0-9a-f]{2}").matches(targetAddress.trim())

    var devices by mutableStateOf<List<NearbyWatch>>(emptyList())
        private set
    var scanning by mutableStateOf(false)
        private set
    var inspecting by mutableStateOf(false)
        private set
    var status by mutableStateOf("Pronto para buscar dispositivos próximos.")
    var gattServices by mutableStateOf<List<GattServiceInfo>>(emptyList())
        private set
    private val handler = Handler(Looper.getMainLooper())
    private var scanner: BluetoothLeScanner? = null
    private var activeCallback: ScanCallback? = null
    private var activeGatt: BluetoothGatt? = null
    private val timeout = Runnable {
        searchCompleted = true
        stop(if (devices.any { matches(it.address) }) "Busca concluída: endereço correspondente encontrado."
            else "Busca concluída: endereço procurado não encontrado nos anúncios recebidos.")
    }
    private val gattTimeout = Runnable { closeGatt("A inspeção GATT excedeu 15 segundos. Nenhuma escrita foi realizada.") }

    fun devicesChangedTarget() {
        devices = emptyList()
        searchCompleted = false
        gattServices = emptyList()
    }

    fun permissions(): Array<String> = if (Build.VERSION.SDK_INT >= 31) {
        arrayOf(Manifest.permission.BLUETOOTH_SCAN, Manifest.permission.BLUETOOTH_CONNECT)
    } else arrayOf(Manifest.permission.ACCESS_FINE_LOCATION)

    fun allowed(): Boolean = permissions().all {
        context.checkSelfPermission(it) == PackageManager.PERMISSION_GRANTED
    }

    @SuppressLint("MissingPermission")
    fun start() {
        if (scanning || inspecting) return
        if (!validTarget()) {
            status = "Informe o endereço Bluetooth completo ou o final, como 0C:CF."
            return
        }
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
            searchCompleted = false
            devices = emptyList()
            gattServices = emptyList()
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
                .sortedWith(compareByDescending<NearbyWatch> { matches(it.address) }.thenByDescending { it.rssi }).take(50)
        }
    }

    @SuppressLint("MissingPermission")
    fun inspectGatt(watch: NearbyWatch) {
        if (inspecting || !allowed()) {
            if (!allowed()) status = "Permissão Bluetooth necessária para inspecionar serviços GATT."
            return
        }
        stop()
        closeGatt()
        gattServices = emptyList()

        val adapter = context.getSystemService(BluetoothManager::class.java)?.adapter
        if (adapter == null || !adapter.isEnabled) {
            status = "Bluetooth indisponível. Ative-o e tente novamente."
            return
        }

        try {
            val device = adapter.getRemoteDevice(watch.address)
            inspecting = true
            status = "Conectando somente para descobrir serviços GATT. Nenhuma escrita será feita."
            val callback = object : BluetoothGattCallback() {
                override fun onConnectionStateChange(gatt: BluetoothGatt, statusCode: Int, newState: Int) {
                    handler.post {
                        if (activeGatt !== gatt) return@post
                        if (statusCode != BluetoothGatt.GATT_SUCCESS) {
                            closeGatt("Falha GATT ($statusCode). Nenhuma alteração foi feita no relógio.")
                            return@post
                        }
                        when (newState) {
                            BluetoothProfile.STATE_CONNECTED -> {
                                status = "Conectado em modo diagnóstico. Descobrindo serviços…"
                                try {
                                    if (!gatt.discoverServices()) {
                                        closeGatt("O relógio recusou a descoberta de serviços. Nenhuma escrita foi realizada.")
                                    }
                                } catch (_: SecurityException) {
                                    closeGatt("Permissão Bluetooth revogada durante a inspeção.")
                                }
                            }
                            BluetoothProfile.STATE_DISCONNECTED ->
                                closeGatt("Relógio desconectado. Nenhuma escrita foi realizada.")
                        }
                    }
                }

                override fun onServicesDiscovered(gatt: BluetoothGatt, statusCode: Int) {
                    handler.post {
                        if (activeGatt !== gatt) return@post
                        if (statusCode == BluetoothGatt.GATT_SUCCESS) {
                            gattServices = mapGattServices(gatt.services)
                            val characteristicCount = gattServices.sumOf { it.characteristics.size }
                            closeGatt(
                                "Inspeção concluída: ${gattServices.size} serviço(s) e $characteristicCount characteristic(s). " +
                                    "Nenhuma leitura de saúde, inscrição ou escrita foi realizada."
                            )
                        } else {
                            closeGatt("Descoberta GATT falhou ($statusCode). Nenhuma escrita foi realizada.")
                        }
                    }
                }
            }
            activeGatt = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                device.connectGatt(context, false, callback, android.bluetooth.BluetoothDevice.TRANSPORT_LE)
            } else {
                device.connectGatt(context, false, callback)
            }
            handler.postDelayed(gattTimeout, 15_000)
        } catch (_: IllegalArgumentException) {
            closeGatt("Endereço Bluetooth inválido ou não mais disponível.")
        } catch (_: SecurityException) {
            closeGatt("Permissão Bluetooth revogada durante a inspeção.")
        } catch (_: IllegalStateException) {
            closeGatt("Bluetooth indisponível durante a inspeção.")
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

    @SuppressLint("MissingPermission")
    fun closeGatt(message: String? = null) {
        handler.removeCallbacks(gattTimeout)
        val gatt = activeGatt
        activeGatt = null
        inspecting = false
        try {
            gatt?.disconnect()
        } catch (_: SecurityException) {
        } catch (_: IllegalStateException) {
        }
        gatt?.close()
        if (message != null) status = message
    }
}

@Composable
fun WatchDiagnosticScreen() {
    val context = LocalContext.current
    val owner = LocalLifecycleOwner.current
    val discovery = remember { WatchDiscovery(context.applicationContext) }
    val session = remember { FitCloudTestSession(context) }
    var selected by remember { mutableStateOf<NearbyWatch?>(null) }
    var target by rememberSaveable { mutableStateOf("0C:CF") }
    discovery.targetAddress = target
    val permissions = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) {
        if (discovery.allowed()) discovery.start()
        else discovery.status = "Busca não iniciada: permissão recusada. Você pode autorizá-la nas configurações do aplicativo."
    }
    DisposableEffect(owner, discovery) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_STOP) {
                session.disconnect("Conexão SDK encerrada ao sair do aplicativo.")
                if (discovery.scanning) discovery.stop("Busca interrompida ao sair do aplicativo.")
                if (discovery.inspecting) discovery.closeGatt("Inspeção interrompida ao sair do aplicativo.")
            }
        }
        owner.lifecycle.addObserver(observer)
        onDispose {
            owner.lifecycle.removeObserver(observer)
            discovery.stop()
            discovery.closeGatt()
            session.disconnect()
        }
    }
    LazyColumn(verticalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxSize()) {
        item {
            Text("Diagnóstico do relógio", style = MaterialTheme.typography.titleLarge)
            Text("Aproxime o C26. Procure pelo endereço exibido no FitCloudPro; o nome pode não aparecer no anúncio Bluetooth.")
            Text("A busca e a inspeção não alteram o relógio. A vinculação e a leitura SDK são ações separadas no dispositivo selecionado.")
        }
        item {
            OutlinedTextField(
                value = target, onValueChange = { target = it.take(17); selected = null; discovery.devicesChangedTarget() },
                enabled = !discovery.scanning && !discovery.inspecting && !session.busy && !session.connected && !session.syncing,
                label = { Text("Endereço do C26 ou final") },
                supportingText = { Text("Final conhecido: 0C:CF. Use o endereço completo para confirmar a identidade.") },
                singleLine = true, modifier = Modifier.fillMaxWidth()
            )
            val found = discovery.devices.firstOrNull { discovery.matches(it.address) }
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Resultado da busca do C26", style = MaterialTheme.typography.titleMedium)
                    Text(when {
                        found != null && target.trim().length == 17 -> "Endereço completo encontrado"
                        found != null -> "Candidato encontrado pelo final do endereço"
                        discovery.scanning -> "Procurando o endereço informado…"
                        discovery.searchCompleted -> "Não encontrado nesta busca"
                        else -> "Faça uma busca para verificar o C26"
                    })
                    if (found != null) {
                        Text("${found.name} • …${found.address.takeLast(5)} • ${found.rssi} dBm")
                        Text("Encontrar o anúncio não confirma conexão nem leitura de sensores.")
                        OutlinedButton(enabled = !discovery.scanning && !discovery.inspecting,
                            onClick = { selected = found }) { Text("Selecionar resultado") }
                    }
                }
            }
        }
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(enabled = !discovery.scanning && !discovery.inspecting, onClick = {
                    selected = null
                    if (discovery.allowed()) discovery.start()
                    else permissions.launch(discovery.permissions())
                }) { Text("Buscar relógio") }
                OutlinedButton(enabled = discovery.scanning, onClick = {
                    discovery.stop("Busca cancelada.")
                }) { Text("Cancelar") }
            }
            Text(discovery.status)
            if (discovery.scanning || discovery.inspecting) LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            if (!discovery.scanning && discovery.devices.isEmpty()) {
                Text("Nenhum resultado nesta busca. Isso pode ocorrer se o relógio já estiver conectado e não anunciar por Bluetooth. Não desfaça o pareamento nem restaure o relógio.")
            }
        }
        selected?.let { watch ->
            item {
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Selecionado: ${watch.name}", style = MaterialTheme.typography.titleMedium)
                        Text("Descoberto por Bluetooth. A próxima ação é somente leitura estrutural do GATT.")
                        Button(
                            enabled = !discovery.scanning && !discovery.inspecting,
                            onClick = { discovery.inspectGatt(watch) }
                        ) { Text("Inspecionar serviços GATT") }
                        FitCloudTestControls(session, watch.address,
                            eligible = discovery.allowed() && target.trim().length == 17 &&
                                watch.address.equals(target.trim(), ignoreCase = true) &&
                                watch.name.equals("C26", ignoreCase = true) &&
                                !discovery.scanning && !discovery.inspecting,
                            beforeConnect = { discovery.stop(); discovery.closeGatt() })
                    }
                }
            }
        }
        if (discovery.gattServices.isNotEmpty()) {
            item {
                Text("Mapa GATT encontrado", style = MaterialTheme.typography.titleLarge)
                Text("Use estes UUIDs para identificar candidatos de sensores antes de qualquer leitura.")
            }
            items(discovery.gattServices, key = { it.uuid }) { service ->
                Card(modifier = Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text("Serviço ${service.uuid}", style = MaterialTheme.typography.titleSmall)
                        if (service.characteristics.isEmpty()) {
                            Text("Sem characteristics expostas.")
                        } else {
                            service.characteristics.forEach { characteristic ->
                                Text("• ${characteristic.uuid} — ${characteristic.properties}")
                            }
                        }
                    }
                }
            }
        }
        items(discovery.devices, key = { it.address }) { device ->
            OutlinedCard(onClick = {
                if (session.busy || session.connected || session.syncing) return@OutlinedCard
                selected = device
                discovery.stop("Dispositivo selecionado. Você pode executar a inspeção GATT segura.")
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