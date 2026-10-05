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

                A leitura é manual e limitada aos últimos 7 dias. O envio exige login Google na HML, cadastro do responsável e criança, declaração da titularidade, autorização e vínculo por código temporário. Depois, confirme no aplicativo antes de cada envio.

                Somente um resumo é enviado: passos de hoje, último batimento e última sessão de sono disponíveis, com horários e origens. O lote de envio pendente e o vínculo ficam criptografados no aparelho com Android Keystore. O lote é apagado após confirmação ou descarte manual. Não são registrados dados de saúde em logs.

                A HML armazena os cadastros e dados de saúde com criptografia no servidor. Apenas a conta responsável pode consultar o histórico. Não há compartilhamento com profissionais nesta versão. Você pode revogar novos envios e desvincular aparelhos na HML; isso não apaga o histórico já recebido. O login é validado pelo Google.

                O aplicativo não altera nem exclui registros do Conexão Saúde e não coleta em segundo plano.

                Confira a titularidade antes de usar: os registros pertencem ao usuário do Conexão Saúde deste aparelho e não são atribuídos automaticamente ao Lucas.

                Você pode negar tipos de dados separadamente e revogar a leitura a qualquer momento nas permissões do Conexão Saúde. Para bloquear o envio, revogue a autorização na HML.

                Esta versão não fornece monitoramento em tempo real nem substitui acompanhamento profissional.
            """.trimIndent()
        }
        setContentView(ScrollView(this).apply { addView(text) })
    }
}

