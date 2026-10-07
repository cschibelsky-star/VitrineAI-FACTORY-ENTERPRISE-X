package br.com.vitrineaipro.assistiva.tablet

import android.app.Application
import android.content.Context
import android.os.Handler
import android.os.Looper
import androidx.compose.runtime.*
import androidx.lifecycle.DefaultLifecycleObserver
import androidx.lifecycle.LifecycleOwner
import androidx.lifecycle.ProcessLifecycleOwner
import com.topstep.fitcloud.sdk.v2.FcSDK
import com.topstep.fitcloud.sdk.v2.features.FcBuiltInFeatures
import com.topstep.fitcloud.sdk.v2.model.data.FcSyncData
import com.topstep.fitcloud.sdk.v2.model.data.FcSyncDataType
import com.topstep.wearkit.base.ProcessLifecycleManager
import com.topstep.wearkit.base.connector.ConnectorState
import io.reactivex.rxjava3.android.schedulers.AndroidSchedulers
import io.reactivex.rxjava3.disposables.CompositeDisposable
import io.reactivex.rxjava3.disposables.Disposable
import io.reactivex.rxjava3.exceptions.UndeliverableException
import io.reactivex.rxjava3.plugins.RxJavaPlugins
import java.util.UUID
import java.util.concurrent.TimeUnit

class LucasApplication : Application() {
    private val process = object : ProcessLifecycleManager(), DefaultLifecycleObserver {
        override fun onStart(owner: LifecycleOwner) = setForeground(true)
        override fun onStop(owner: LifecycleOwner) = setForeground(false)
    }
    override fun onCreate() {
        super.onCreate()
        ProcessLifecycleOwner.get().lifecycle.addObserver(process)
    }

    // No Bluetooth operation at application startup.
    val watchSdk: FcSDK by lazy {
        val ignored = FcSDK.rxJavaPluginsIgnoreExceptions()
        RxJavaPlugins.setErrorHandler { error ->
            val cause = if (error is UndeliverableException) error.cause ?: error else error
            if (ignored.none { it.isInstance(cause) }) {
                Thread.currentThread().uncaughtExceptionHandler?.uncaughtException(Thread.currentThread(), cause)
            }
        }
        FcSDK.Builder(this, process)
            .setBuiltInFeatures(FcBuiltInFeatures(autoSetLanguage = false, autoSetTime = false))
            .build()
    }
}

data class WatchTestProfile(val male: Boolean, val age: Int, val height: Float, val weight: Float) {
    fun valid() = age in 1..120 && height in 40f..250f && weight in 2f..300f
}

data class WatchTestSample(val kind: String, val value: String, val timestamp: Long)

class FitCloudTestSession(context: Context) {
    private val app = context.applicationContext as LucasApplication
    private val preferences = app.getSharedPreferences("watch_test_identity", Context.MODE_PRIVATE)
    private val handler = Handler(Looper.getMainLooper())
    private val observers = CompositeDisposable()
    private var sdk: FcSDK? = null
    private var syncTask: Disposable? = null
    private var epoch = 0
    private var binding = false
    private var currentAddress: String? = null
    private var currentProfile: WatchTestProfile? = null
    var busy by mutableStateOf(false)
        private set
    var connected by mutableStateOf(false)
        private set
    var syncing by mutableStateOf(false)
        private set
    var status by mutableStateOf("SDK pronto para teste; nenhuma vinculação iniciada.")
        private set
    var samples by mutableStateOf<List<WatchTestSample>>(emptyList())
        private set
    var boundAddress by mutableStateOf(preferences.getString("bound_address", null))
        private set
    private val connectionTimeout = Runnable {
        disconnect("Conexão excedeu 60 segundos. Não foi feita nova vinculação automática.")
    }

    fun canLogin(address: String) = boundAddress?.equals(address, ignoreCase = true) == true
    private fun identity(): String {
        preferences.getString("identity", null)?.let { return it }
        val id = UUID.randomUUID().toString().replace("-", "").take(24)
        check(preferences.edit().putString("identity", id).commit()) {
            "Não foi possível guardar a identidade de teste"
        }
        return id
    }

    fun connect(address: String, profile: WatchTestProfile, newBind: Boolean) {
        if (busy || connected || syncing) return
        if (!Regex("(?i)([0-9a-f]{2}:){5}[0-9a-f]{2}").matches(address) || !profile.valid()) {
            status = "Confira o endereço completo e o perfil da pessoa que usa o relógio."
            return
        }
        if (!newBind && !canLogin(address)) {
            status = "Este relógio ainda não tem vínculo confirmado com a identidade deste aplicativo."
            return
        }
        disconnect()
        val ticket = epoch
        try {
            val userId = identity()
            val instance = app.watchSdk
            sdk = instance
            val connector = instance.connector
            binding = newBind
            currentAddress = address
            currentProfile = profile
            samples = emptyList()
            busy = true
            status = if (newBind) "Vinculando ao Projeto Lucas. Os registros de teste anteriores podem ser apagados."
                else "Reconectando com a identidade de teste já guardada…"
            observers.add(connector.observerConnectorError()
                .observeOn(AndroidSchedulers.mainThread())
                .subscribe({ error ->
                    if (ticket == epoch) disconnect("Falha de autenticação/conexão (${error.javaClass.simpleName}). Sem nova vinculação automática.")
                }, { error ->
                    if (ticket == epoch) disconnect("Falha do SDK (${error.javaClass.simpleName}).")
                }))
            observers.add(connector.observerConnectorState()
                .observeOn(AndroidSchedulers.mainThread())
                .subscribe({ state ->
                    if (ticket != epoch) return@subscribe
                    when (state) {
                        ConnectorState.CONNECTED -> {
                            if (binding) {
                                if (!preferences.edit().putString("bound_address", address).commit()) {
                                    disconnect("Vínculo pode ter sido criado, mas não foi possível guardar o endereço. Não repita a vinculação.")
                                    return@subscribe
                                }
                                boundAddress = address
                                binding = false
                            }
                            handler.removeCallbacks(connectionTimeout)
                            busy = false
                            connected = true
                            status = "C26 autenticado pelo SDK. Toque em Sincronizar dados para testar a leitura."
                        }
                        ConnectorState.DISCONNECTED -> {
                            if (connected) disconnect("Conexão encerrada. Reconecte pelo login para continuar.")
                        }
                        else -> {
                            if (busy) status = "Preparando conexão SDK: ${state.name}"
                        }
                    }
                }, { error ->
                    if (ticket == epoch) disconnect("Falha de estado (${error.javaClass.simpleName}).")
                }))
            handler.postDelayed(connectionTimeout, 60_000)
            connector.connect(address, userId, newBind, profile.male, profile.age, profile.height, profile.weight)
        } catch (error: Exception) {
            disconnect("Não foi possível iniciar o SDK (${error.javaClass.simpleName}).")
        }
    }

    fun sync() {
        if (!connected || syncing) return
        val instance = sdk ?: return
        val ticket = epoch
        syncing = true
        samples = emptyList()
        status = "Sincronizando registros disponíveis no relógio…"
        try {
            syncTask = instance.connector.dataFeature().syncData()
                .timeout(90, TimeUnit.SECONDS)
                .observeOn(AndroidSchedulers.mainThread())
                .subscribe({ data ->
                    if (ticket == epoch) consume(data)
                }, { error ->
                    if (ticket == epoch) {
                        val count = samples.size
                        disconnect("Sincronização interrompida (${error.javaClass.simpleName}); $count registro(s) recebidos parcialmente.")
                    }
                }, {
                    if (ticket == epoch) {
                        syncing = false
                        status = if (samples.isEmpty()) "Sincronização concluída sem registros dos tipos exibidos. Gere novos dados no relógio e tente novamente."
                            else "Sincronização concluída: ${samples.size} registro(s) de teste recebidos."
                    }
                })
        } catch (error: Exception) {
            disconnect("Falha ao sincronizar (${error.javaClass.simpleName}).")
        }
    }

    private fun consume(data: FcSyncData) {
        val records = when (data.type) {
            FcSyncDataType.HEART_RATE -> data.toHeartRate()?.map {
                WatchTestSample("Batimento histórico", "${it.heartRate} bpm", it.timestamp)
            }
            FcSyncDataType.HEART_RATE_MEASURE -> data.toHeartRateMeasure()?.map {
                WatchTestSample("Batimento medido", "${it.heartRate} bpm", it.timestamp)
            }
            FcSyncDataType.STEP -> data.toStep()?.map {
                WatchTestSample("Passos do intervalo", "${it.step}", it.timestamp)
            }
            FcSyncDataType.SLEEP -> data.toSleep()?.map {
                WatchTestSample("Registro de sono", "${it.items.size} segmento(s); duração ainda não calculada", it.timestamp)
            }
            FcSyncDataType.TODAY_TOTAL_DATA -> data.toTodayTotal()?.let {
                listOf(WatchTestSample("Total de passos do relógio", "${it.step}", it.timestamp))
            }
            else -> null
        }
        samples = (samples + records.orEmpty()).distinct()
            .sortedByDescending { it.timestamp }.take(1000)
    }

    fun disconnect(message: String? = null) {
        epoch++
        handler.removeCallbacks(connectionTimeout)
        syncTask?.dispose()
        syncTask = null
        observers.clear()
        runCatching { sdk?.connector?.close() }
        busy = false
        connected = false
        syncing = false
        binding = false
        if (message != null) status = message
    }
}
