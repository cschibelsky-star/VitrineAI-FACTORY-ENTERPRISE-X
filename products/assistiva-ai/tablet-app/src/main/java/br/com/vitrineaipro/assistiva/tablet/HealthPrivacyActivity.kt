package br.com.vitrineaipro.assistiva.tablet

import android.app.Activity
import android.os.Bundle
import android.widget.ScrollView
import android.widget.TextView

class HealthPrivacyActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val padding = (24 * resources.displayMetrics.density).toInt()
        val text = TextView(this).apply {
            textSize = 18f
            setPadding(padding, padding * 2, padding, padding)
            text = """
                Projeto Lucas — uso dos dados de saúde

                Esta versão solicita somente leitura de passos, frequência cardíaca e sessões de sono para exibir os registros disponíveis neste aparelho.

                A leitura é manual e limitada aos últimos 7 dias. Os dados são exibidos apenas na memória da tela: não são gravados em arquivos, logs ou enviados à HML ou a serviços externos.

                O aplicativo não altera nem exclui registros do Conexão Saúde e não coleta em segundo plano.

                Confira a titularidade antes de usar: os registros pertencem ao usuário do Conexão Saúde deste aparelho e não são atribuídos automaticamente ao Lucas.

                Você pode negar tipos de dados separadamente e revogar a autorização a qualquer momento nas permissões do Conexão Saúde.

                Esta versão não fornece monitoramento em tempo real nem substitui acompanhamento profissional.
            """.trimIndent()
        }
        setContentView(ScrollView(this).apply { addView(text) })
    }
}
