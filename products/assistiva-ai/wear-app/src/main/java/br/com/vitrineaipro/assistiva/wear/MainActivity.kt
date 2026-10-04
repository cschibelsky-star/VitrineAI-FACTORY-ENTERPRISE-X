package br.com.vitrineaipro.assistiva.wear

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.Composable
import androidx.wear.compose.material3.MaterialTheme
import androidx.wear.compose.material3.Text

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { WearStatus() }
    }
}

@Composable
private fun WearStatus() {
    MaterialTheme {
        Text("Monitoramento ativo")
    }
}
