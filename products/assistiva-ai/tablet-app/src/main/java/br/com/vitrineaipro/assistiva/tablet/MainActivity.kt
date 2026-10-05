package br.com.vitrineaipro.assistiva.tablet

import android.os.Bundle
import android.speech.tts.TextToSpeech
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.Image
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.Alignment
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import java.util.Locale

private data class CommunicationCard(val phrase: String, val image: Int)

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            MaterialTheme {
                val ttsState = remember { mutableStateOf<TextToSpeech?>(null) }
                var selected by remember { mutableStateOf<String?>(null) }
                var health by remember { mutableStateOf(false) }
                var voiceStatus by remember { mutableStateOf("Preparando voz…") }
                DisposableEffect(Unit) {
                    lateinit var tts: TextToSpeech
                    tts = TextToSpeech(this@MainActivity) { status ->
                        if (status == TextToSpeech.SUCCESS) {
                            val result = tts.setLanguage(Locale("pt", "BR"))
                            if (result >= TextToSpeech.LANG_AVAILABLE) {
                                ttsState.value = tts
                                voiceStatus = "Toque em um card para falar."
                            } else voiceStatus = "Voz em português indisponível neste aparelho."
                        } else voiceStatus = "Não foi possível iniciar a voz."
                    }
                    onDispose { tts.stop(); tts.shutdown() }
                }
                val cards = listOf(
                    CommunicationCard("Quero água", R.drawable.card_water),
                    CommunicationCard("Quero comer", R.drawable.card_food),
                    CommunicationCard("Quero ir ao banheiro", R.drawable.card_toilet),
                    CommunicationCard("Quero uma pausa", R.drawable.card_pause),
                    CommunicationCard("Está muito barulhento", R.drawable.card_noise),
                    CommunicationCard("Quero meu fone", R.drawable.card_headphones),
                    CommunicationCard("Quero sair", R.drawable.card_exit),
                    CommunicationCard("Quero ajuda", R.drawable.card_help),
                    CommunicationCard("Estou com dor", R.drawable.card_pain),
                    CommunicationCard("Não quero", R.drawable.card_refuse),
                    CommunicationCard("Sim", R.drawable.card_yes),
                    CommunicationCard("Não", R.drawable.card_no)
                )
                Surface(color = Color(0xFFF5F7FB), modifier = Modifier.fillMaxSize()) {
                    Column(
                        modifier = Modifier.fillMaxSize().windowInsetsPadding(WindowInsets.safeDrawing).padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text("Projeto Lucas", style = MaterialTheme.typography.headlineSmall)
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            OutlinedButton(onClick = { health = false }) { Text("Comunicação") }
                            OutlinedButton(onClick = { health = true }) { Text("Saúde") }
                        }
                        if (health) {
                            Text("Saúde e sincronização", style = MaterialTheme.typography.titleLarge)
                            Text("Integração ainda não implementada", style = MaterialTheme.typography.titleMedium)
                            Text("Este APK não solicita permissões nem lê dados do Conexão Saúde.")
                            Text("Última leitura: nenhuma\nEnvio à HML: não configurado")
                            Text("Os registros vistos no Conexão Saúde ainda não foram importados pelo Projeto Lucas.")
                        } else {
                            Text("A criança escolhe. A IA apenas sugere.")
                            Text(voiceStatus, style = MaterialTheme.typography.bodyMedium)
                            LazyVerticalGrid(
                                columns = GridCells.Adaptive(148.dp),
                                modifier = Modifier.weight(1f),
                                contentPadding = PaddingValues(bottom = 16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp),
                                horizontalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(cards, key = { it.phrase }) { card ->
                                    val active = selected == card.phrase
                                    OutlinedCard(
                                        onClick = {
                                            selected = card.phrase
                                            ttsState.value?.speak(card.phrase, TextToSpeech.QUEUE_FLUSH, null, "aac-${card.image}")
                                        },
                                        shape = RoundedCornerShape(20.dp),
                                        border = BorderStroke(if (active) 3.dp else 1.dp, if (active) Color(0xFF66509A) else Color(0xFFD7DAE2)),
                                        colors = CardDefaults.outlinedCardColors(containerColor = if (active) Color(0xFFEDE7F6) else Color.White),
                                        modifier = Modifier.fillMaxWidth().height(200.dp)
                                    ) {
                                        Column(
                                            modifier = Modifier.fillMaxSize().padding(12.dp),
                                            horizontalAlignment = Alignment.CenterHorizontally,
                                            verticalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterVertically)
                                        ) {
                                            Image(painterResource(card.image), contentDescription = null, modifier = Modifier.size(88.dp))
                                            Text(card.phrase, textAlign = TextAlign.Center, style = MaterialTheme.typography.titleMedium)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
