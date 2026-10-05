package br.com.vitrineaipro.assistiva.tablet

import android.os.Bundle
import android.speech.tts.TextToSpeech
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.Alignment
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.res.imageResource
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.IntSize
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
                val atlas = ImageBitmap.imageResource(R.drawable.communication_atlas)
                val cards = listOf(
                    CommunicationCard("Quero água", 0),
                    CommunicationCard("Quero comer", 1),
                    CommunicationCard("Quero ir ao banheiro", 2),
                    CommunicationCard("Quero uma pausa", 3),
                    CommunicationCard("Está muito barulhento", 4),
                    CommunicationCard("Quero meu fone", 5),
                    CommunicationCard("Quero sair", 6),
                    CommunicationCard("Quero ajuda", 7),
                    CommunicationCard("Estou com dor", 8),
                    CommunicationCard("Não quero", 9),
                    CommunicationCard("Sim", 10),
                    CommunicationCard("Não", 11)
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
                            HealthScreen()
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
                                        border = BorderStroke(if (active) 3.dp else 3.dp, if (active) Color(0xFF66509A) else Color(0xFF202020)),
                                        colors = CardDefaults.outlinedCardColors(containerColor = if (active) Color(0xFFEDE7F6) else Color.White),
                                        modifier = Modifier.fillMaxWidth().heightIn(min = 232.dp)
                                    ) {
                                        Column(
                                            modifier = Modifier.fillMaxWidth().padding(12.dp),
                                            horizontalAlignment = Alignment.CenterHorizontally,
                                            verticalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterVertically)
                                        ) {
                                            Canvas(modifier = Modifier.fillMaxWidth().aspectRatio(1f)) {
                                                val column = card.image % 3
                                                val row = card.image / 3
                                                val left = column * atlas.width / 3
                                                val top = row * atlas.height / 4
                                                val right = (column + 1) * atlas.width / 3
                                                val bottom = (row + 1) * atlas.height / 4
                                                drawImage(
                                                    image = atlas,
                                                    srcOffset = IntOffset(left, top),
                                                    srcSize = IntSize(right - left, bottom - top),
                                                    dstSize = IntSize(size.width.toInt(), size.height.toInt())
                                                )
                                            }
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
