package br.com.vitrineaipro.assistiva.tablet

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.selection.selectable
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.unit.dp
import java.text.DateFormat
import java.util.Date

@Composable
fun FitCloudTestControls(
    session: FitCloudTestSession,
    address: String,
    eligible: Boolean,
    beforeConnect: () -> Unit
) {
    var age by rememberSaveable { mutableStateOf("") }
    var height by rememberSaveable { mutableStateOf("") }
    var weight by rememberSaveable { mutableStateOf("") }
    var male by rememberSaveable { mutableStateOf<Boolean?>(null) }
    var consent by rememberSaveable { mutableStateOf(false) }
    var confirmBind by remember { mutableStateOf(false) }
    val profile = male?.let { sex ->
        val a = age.toIntOrNull()
        val h = height.replace(',', '.').toFloatOrNull()
        val w = weight.replace(',', '.').toFloatOrNull()
        if (a != null && h != null && w != null) WatchTestProfile(sex, a, h, w) else null
    }
    val idle = !session.busy && !session.connected && !session.syncing
    val ready = eligible && consent && profile?.valid() == true && idle
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text("Leitura direta pelo SDK • versão 0.9.0", style = MaterialTheme.typography.titleMedium)
        Text("Teste local. Os registros recebidos não são atribuídos ao Lucas nem enviados à HML.")
        Text("Antes de conectar, force a parada do FitCloudPro para liberar a conexão Bluetooth.")
        if (!eligible) Text("Informe o endereço completo no início da tela e selecione o C26 correspondente.")
        Text("Perfil da pessoa que está usando o relógio. O SDK envia estes parâmetros ao dispositivo.")
        OutlinedTextField(age, { age = it.take(3) }, enabled = idle,
            label = { Text("Idade em anos") }, singleLine = true, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(height, { height = it.take(6) }, enabled = idle,
            label = { Text("Altura em centímetros") }, singleLine = true, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(weight, { weight = it.take(6) }, enabled = idle,
            label = { Text("Peso em quilogramas") }, singleLine = true, modifier = Modifier.fillMaxWidth())
        listOf(true to "Masculino", false to "Feminino").forEach { (value, label) ->
            Row(Modifier.fillMaxWidth().selectable(
                selected = male == value, enabled = idle, role = Role.RadioButton,
                onClick = { male = value }
            )) {
                RadioButton(selected = male == value, enabled = idle, onClick = null)
                Text(label, modifier = Modifier.padding(top = 12.dp))
            }
        }
        Row {
            Checkbox(checked = consent, enabled = idle, onCheckedChange = { consent = it })
            Text("Confirmo que são dados de teste e autorizo enviar o perfil ao relógio.",
                modifier = Modifier.padding(top = 8.dp))
        }
        if (session.canLogin(address)) {
            Button(enabled = ready, onClick = {
                beforeConnect()
                profile?.let { session.connect(address, it, false) }
            }) { Text("Reconectar C26 pelo login") }
        } else {
            Button(enabled = ready, onClick = { confirmBind = true }) {
                Text("Criar vínculo de teste com C26")
            }
        }
        if (session.busy || session.syncing) LinearProgressIndicator(Modifier.fillMaxWidth())
        Text(session.status)
        Button(enabled = session.connected && !session.syncing, onClick = { session.sync() }) {
            Text("Sincronizar dados do relógio")
        }
        OutlinedButton(enabled = session.busy || session.connected || session.syncing,
            onClick = { session.disconnect("Conexão SDK encerrada pelo usuário.") }) {
            Text("Encerrar conexão SDK")
        }
        Text("A primeira vinculação pode apagar o histórico de teste. Guarde esta instalação: desinstalar pode perder a identidade usada no relógio.")
        if (session.samples.isNotEmpty()) {
            Text("Registros de teste recebidos", style = MaterialTheme.typography.titleMedium)
            Text("Origem: SDK FitCloudPro • dispositivo …${address.takeLast(5)} • limite local: 1.000 registros")
            session.samples.take(30).forEach { sample ->
                Text("${sample.kind}: ${sample.value}\n${DateFormat.getDateTimeInstance().format(Date(sample.timestamp))}")
            }
            if (session.samples.size > 30) Text("Exibindo os 30 registros mais recentes.")
        }
    }
    if (confirmBind) AlertDialog(
        onDismissRequest = { confirmBind = false },
        title = { Text("Vincular C26 ao Projeto Lucas?") },
        text = { Text("Dispositivo …${address.takeLast(5)}. Esta ação pode apagar passos, sono e batimentos anteriores e substituir o vínculo do FitCloudPro. Não é possível desfazer a perda de registros. Mantenha esta tela aberta durante a operação.") },
        confirmButton = {
            TextButton(enabled = ready, onClick = {
                confirmBind = false
                beforeConnect()
                profile?.let { session.connect(address, it, true) }
            }) { Text("Vincular e descartar testes anteriores") }
        },
        dismissButton = { TextButton(onClick = { confirmBind = false }) { Text("Cancelar") } }
    )
}
