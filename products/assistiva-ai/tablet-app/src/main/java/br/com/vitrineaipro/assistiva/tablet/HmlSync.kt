package br.com.vitrineaipro.assistiva.tablet

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL
import java.security.KeyStore
import java.util.UUID
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

private const val HML = "https://lucas.hml.vitrineiapro.com.br"
/** Secrets and retry snapshots are encrypted with a non-exportable Android Keystore key. */
private class HmlVault(context: Context) {
    private val prefs = context.getSharedPreferences("hml_secure", Context.MODE_PRIVATE)
    private fun key(): SecretKey {
        val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (store.getKey("lucas_hml", null) as? SecretKey)?.let { return it }
        return KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore").apply {
            init(KeyGenParameterSpec.Builder("lucas_hml", KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE).build())
        }.generateKey()
    }
    fun get(name: String): String? {
        val stored = prefs.getString(name, null) ?: return null
        return try {
            val bytes = Base64.decode(stored, Base64.NO_WRAP)
            val cipher = Cipher.getInstance("AES/GCM/NoPadding")
            cipher.init(Cipher.DECRYPT_MODE, key(), GCMParameterSpec(128, bytes.copyOfRange(0, 12)))
            String(cipher.doFinal(bytes.copyOfRange(12, bytes.size)), Charsets.UTF_8)
        } catch (_: Exception) { prefs.edit().remove(name).apply(); null }
    }
    fun set(name: String, value: String?) {
        if (value == null) { prefs.edit().remove(name).apply(); return }
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.ENCRYPT_MODE, key())
        prefs.edit().putString(name, Base64.encodeToString(cipher.iv + cipher.doFinal(value.toByteArray(Charsets.UTF_8)), Base64.NO_WRAP)).commit()
    }
    fun clear() { prefs.edit().clear().commit() }
}
private suspend fun hmlRequest(path: String, body: JSONObject, token: String? = null): JSONObject = withContext(Dispatchers.IO) {
    val conn = URL(HML + path).openConnection() as HttpURLConnection
    try {
        conn.requestMethod = "POST"; conn.connectTimeout = 15000; conn.readTimeout = 20000
        conn.instanceFollowRedirects = false; conn.doOutput = true
        conn.setRequestProperty("Content-Type", "application/json")
        token?.let { conn.setRequestProperty("Authorization", "Bearer $it") }
        conn.outputStream.use { it.write(body.toString().toByteArray(Charsets.UTF_8)) }
        val code = conn.responseCode
        val text = (if (code in 200..299) conn.inputStream else conn.errorStream)?.bufferedReader()?.use { it.readText() }.orEmpty()
        val data = try { JSONObject(text) } catch (_: Exception) { JSONObject() }
        if (code !in 200..299) throw IllegalStateException(data.optString("detail", "Falha de conexão com a HML ($code)."))
        data
    } finally { conn.disconnect() }
}

@Composable
fun HmlSyncCard(records: JSONArray?, readAt: String?) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val vault = remember { HmlVault(context) }
    var link by remember { mutableStateOf(vault.get("link")?.let { JSONObject(it) }) }
    var receipt by remember { mutableStateOf(vault.get("receipt")) }
    var pending by remember { mutableStateOf(vault.get("pending")) }
    var code by remember { mutableStateOf("") }
    var confirmed by remember { mutableStateOf(false) }
    var busy by remember { mutableStateOf(false) }
    var status by remember { mutableStateOf("Nenhum envio realizado nesta sessão.") }
    LaunchedEffect(readAt, link) { confirmed = false }
    Card(Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Text("Vincular à HML", style = MaterialTheme.typography.titleLarge)
            Text("Entre com Google na HML, cadastre o responsável e a criança, confirme de quem são os registros e autorize o envio.")
            OutlinedButton(enabled = !busy, onClick = {
                try { context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("$HML/#saude"))) }
                catch (_: Exception) { status = "Abra $HML no navegador." }
            }) { Text("Entrar com Google na HML") }
            if (link == null) {
                OutlinedTextField(value = code, onValueChange = { code = it.uppercase().filter { ch -> ch in "0123456789ABCDEF" }.take(12) },
                    label = { Text("Código de 12 caracteres gerado na HML") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
                Button(enabled = !busy && code.length == 12, onClick = {
                    scope.launch {
                        busy = true
                        try {
                            val result = hmlRequest("/api/device/exchange", JSONObject().put("code", code))
                            vault.set("link", result.toString()); link = result; code = ""
                            status = "Aparelho vinculado. Confira a criança antes de enviar."
                        } catch (e: Exception) { status = e.message ?: "Não foi possível vincular. Tente novamente." }
                        finally { busy = false }
                    }
                }) { Text("Vincular aparelho") }
            } else {
                Text("Criança vinculada: ${link!!.getString("child_name")}", style = MaterialTheme.typography.titleMedium)
                Text("Se os dados forem seus ou de outra pessoa, não envie para esta criança.")
                Row {
                    Checkbox(checked = confirmed, enabled = !busy, onCheckedChange = { confirmed = it })
                    Text("Conferi os registros e confirmo que pertencem a ${link!!.getString("child_name")}. Autorizo este envio.", Modifier.padding(top = 12.dp))
                }
                if (pending != null) {
                    Text("Lote pendente a enviar (confira estes registros):")
                    val savedRecords = JSONObject(pending!!).getJSONArray("records")
                    for (i in 0 until savedRecords.length()) {
                        val r = savedRecords.getJSONObject(i)
                        Text("${r.getString("type")}: ${r.get("value")} · ${r.getString("start")} → ${r.getString("end")} · ${r.getString("origin")}")
                    }
                    Text("Reenvie para verificar o recebimento sem duplicar o lote, ou descarte-o antes de enviar uma nova leitura.")
                }
                Button(enabled = !busy && confirmed && (pending != null || (records != null && records.length() > 0)),
                    modifier = Modifier.fillMaxWidth(), onClick = {
                        scope.launch {
                            busy = true
                            try {
                                val body = pending?.let { JSONObject(it) } ?: JSONObject()
                                    .put("batch_id", UUID.randomUUID().toString()).put("ownership_confirmed", true).put("records", records)
                                if (pending == null) {
                                    pending = body.toString(); vault.set("pending", pending)
                                }
                                val result = hmlRequest("/api/device/uploads", body, link!!.getString("token"))
                                if (!result.getBoolean("ok") || result.getString("receipt_id").isBlank() || result.getInt("record_count") != body.getJSONArray("records").length())
                                    throw IllegalStateException("Resposta incompleta. Recebimento ainda não confirmado.")
                                receipt = "HML recebeu ${result.getInt("record_count")} registros em ${result.getString("received_at")}\nProtocolo: ${result.getString("receipt_id")}\nCriança: ${link!!.getString("child_name") }"
                                vault.set("receipt", receipt); vault.set("pending", null); pending = null
                                confirmed = false; status = "Recebimento confirmado. Consulte o histórico na HML."
                            } catch (e: Exception) { status = (e.message ?: "Sem conexão.") + " Recebimento não confirmado; o lote permanece para tentativa manual." }
                            finally { busy = false }
                        }
                    }) { Text(if (pending != null) "Verificar / reenviar lote pendente" else "Enviar leitura à HML") }
                if (pending != null) OutlinedButton(enabled = !busy, onClick = { vault.set("pending", null); pending = null; confirmed = false; status = "Lote local descartado. Isso não apaga dados já recebidos na HML." }) { Text("Descartar lote local") }
                OutlinedButton(enabled = !busy, onClick = { vault.clear(); link = null; pending = null; receipt = null; confirmed = false; status = "Vínculo removido deste aparelho. Revogue também na HML para invalidar a autorização." }) { Text("Desvincular este aparelho") }
            }
            if (busy) LinearProgressIndicator(Modifier.fillMaxWidth())
            Text(status)
            Text(receipt ?: "Último recebimento confirmado: nenhum.")
            Text("Envio manual de um resumo: passos de hoje, último batimento e última sessão de sono disponíveis. Sem coleta em segundo plano.")
        }
    }
}
