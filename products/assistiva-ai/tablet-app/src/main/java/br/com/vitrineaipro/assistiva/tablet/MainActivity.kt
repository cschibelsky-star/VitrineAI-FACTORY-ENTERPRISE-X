package br.com.vitrineaipro.assistiva.tablet

import android.os.Bundle
import android.speech.tts.TextToSpeech
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import java.util.Locale

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            MaterialTheme {
                val ttsState = remember { mutableStateOf<TextToSpeech?>(null) }
                DisposableEffect(Unit) {
                    lateinit var tts: TextToSpeech
                    tts = TextToSpeech(this@MainActivity) { status ->
                        if (status == TextToSpeech.SUCCESS) {
                            tts.language = Locale("pt", "BR")
                            ttsState.value = tts
                        }
                    }
                    onDispose { tts.shutdown() }
                }

                val cards = listOf(
                    "Quero água",
                    "Quero comer",
                    "Quero ir ao banheiro",
                    "Quero uma pausa",
                    "Está muito barulhento",
                    "Quero meu fone",
                    "Quero sair",
                    "Quero ajuda",
                    "Estou com dor",
                    "Não quero",
                    "Sim",
                    "Não"
                )

                Column(
                    modifier = Modifier.fillMaxSize().padding(24.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    Text("Comunicação", style = MaterialTheme.typography.headlineMedium)
                    Text("A criança escolhe. A IA apenas sugere.")
                    LazyVerticalGrid(
                        columns = GridCells.Fixed(3),
                        contentPadding = PaddingValues(4.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(cards) { phrase ->
                            Button(onClick = {
                                ttsState.value?.speak(
                                    phrase,
                                    TextToSpeech.QUEUE_FLUSH,
                                    null,
                                    "aac-${phrase.hashCode()}"
                                )
                            }) {
                                Text(phrase)
                            }
                        }
                    }
                }
            }
        }
    }
}
