def status_item(resultados, nome_parcial):
    nome_parcial = nome_parcial.lower()
    for r in resultados or []:
        if nome_parcial in r.get("item", "").lower():
            return r.get("status") == "Encontrado", r.get("arquivo", "")
    return False, ""


def montar_evidencias(resultados):
    exclusividade, arq_exclusividade = status_item(resultados, "exclusividade")
    consagracao, arq_consagracao = status_item(resultados, "consagração")
    justificativa, arq_justificativa = status_item(resultados, "justificativa de preço")
    notas, arq_notas = status_item(resultados, "notas fiscais")
    cnpj, arq_cnpj = status_item(resultados, "cnpj")
    qsa, arq_qsa = status_item(resultados, "qsa")
    contrato, arq_contrato = status_item(resultados, "contrato social")
    federal, arq_federal = status_item(resultados, "certidão federal")
    estadual, arq_estadual = status_item(resultados, "certidão estadual")
    municipal, arq_municipal = status_item(resultados, "certidão municipal")
    fgts, arq_fgts = status_item(resultados, "fgts")
    cndt, arq_cndt = status_item(resultados, "cndt")
    orcamento, arq_orcamento = status_item(resultados, "orçamentária")
    gestor, arq_gestor = status_item(resultados, "gestor")

    regularidade_fiscal = federal and fgts and cndt
    regularidade_juridica = cnpj and contrato

    return {
        "exclusividade": {"ok": exclusividade, "arquivo": arq_exclusividade},
        "consagracao": {"ok": consagracao, "arquivo": arq_consagracao},
        "justificativa_preco": {"ok": justificativa or notas, "arquivo": arq_justificativa or arq_notas},
        "notas_fiscais": {"ok": notas, "arquivo": arq_notas},
        "regularidade_fiscal": {"ok": regularidade_fiscal, "arquivo": ", ".join([a for a in [arq_federal, arq_fgts, arq_cndt] if a])},
        "regularidade_juridica": {"ok": regularidade_juridica, "arquivo": ", ".join([a for a in [arq_cnpj, arq_contrato, arq_qsa] if a])},
        "disponibilidade_orcamentaria": {"ok": orcamento, "arquivo": arq_orcamento},
        "gestor_fiscal": {"ok": True, "arquivo": "Peça gerada pelo sistema com gestor e fiscal padrão definidos"},
        "certidoes": {
            "ok": federal and estadual and municipal and fgts and cndt,
            "arquivo": ", ".join([a for a in [arq_federal, arq_estadual, arq_municipal, arq_fgts, arq_cndt] if a])
        },
    }


def ref_doc(evidencias, chave):
    ev = (evidencias or {}).get(chave, {})
    arq = ev.get("arquivo")
    if ev.get("ok") and arq:
        return f"documento identificado nos autos como '{arq}'"
    if ev.get("ok"):
        return "documento localizado nos autos"
    return "documento ainda não identificado nos autos"


def paragrafo_exclusividade(evidencias):
    if evidencias.get("exclusividade", {}).get("ok"):
        return (
            f"Consta dos autos {ref_doc(evidencias, 'exclusividade')}, apontando representação exclusiva para a comercialização da apresentação artística pretendida. "
            "A aptidão desse documento para fundamentar a inexigibilidade deve ser aferida pela verificação conjunta de sua autenticidade, vigência, abrangência territorial e temporal, "
            "identificação clara da artista, identificação da empresa representante, poderes de quem subscreve e compatibilidade com a data do evento. "
            "Confirmados esses elementos, a Administração estará diante de hipótese de inviabilidade de competição, pois a escolha não recai sobre serviço artístico genérico, "
            "mas sobre apresentação de artista determinada, cuja contratação somente pode ser viabilizada por intermédio de representante exclusivo ou diretamente com a artista."
        )
    return (
        "Não foi identificado, até esta fase da instrução, documento apto a comprovar a representação exclusiva da artista ou de seu empresário. "
        "A ausência desse elemento impede conclusão segura pela inexigibilidade, pois a Administração deve demonstrar que não há pluralidade de fornecedores em condições de oferecer "
        "a mesma apresentação artística pretendida. Antes do prosseguimento, deve ser juntado documento formal de exclusividade, com indicação da artista, representante, prazo de validade, "
        "abrangência e poderes de quem o emitiu ou subscreveu."
    )


def paragrafo_consagracao(evidencias):
    if evidencias.get("consagracao", {}).get("ok"):
        return (
            f"Consta dos autos {ref_doc(evidencias, 'consagracao')}, utilizado como elemento de demonstração da consagração pública ou crítica especializada. "
            "Para fins de motivação da contratação direta, a consagração deve ser examinada a partir de dados objetivos, tais como histórico de apresentações, reconhecimento no segmento artístico, "
            "alcance perante o público, registros de mídia, premiações, relevância cultural, presença em plataformas digitais, agenda pública e compatibilidade entre a notoriedade demonstrada e o porte do evento municipal. "
            "Não se trata de mera preferência administrativa, mas de escolha motivada de atração artística com reconhecimento público compatível com a finalidade do evento."
        )
    return (
        "Não foram identificados elementos suficientes de consagração pública ou crítica especializada. A instrução deve ser complementada com release, clipping, comprovação de agenda, "
        "histórico de apresentações, registros de mídia, dados de audiência, premiações, reconhecimento no segmento artístico ou documentos equivalentes que permitam aferir, de modo objetivo, "
        "a pertinência da escolha da artista para o evento."
    )


def paragrafo_preco(evidencias, valor, origem):
    valor_txt = valor or "valor ainda não consolidado"
    origem_txt = origem or "origem documental ainda não consolidada"
    if evidencias.get("justificativa_preco", {}).get("ok"):
        return (
            f"O valor de referência considerado na instrução é {valor_txt}, com origem principal em {origem_txt}. "
            f"Constam dos autos elementos de suporte à justificativa de preço, especialmente {ref_doc(evidencias, 'justificativa_preco')}. "
            "A aferição da compatibilidade deve observar que notas fiscais, contratos pretéritos e contratações similares não substituem a proposta atual nem a requisição do processo, "
            "mas funcionam como parâmetros de controle para verificar se o preço proposto guarda razoabilidade com valores praticados em apresentações de natureza, porte e condições semelhantes. "
            "A conclusão pela compatibilidade depende de análise comparativa mínima, considerando data da contratação similar, contratante, localidade, objeto executado, porte do evento, artista, valor e eventuais diferenças de escopo."
        )
    return (
        f"O valor de referência indicado é {valor_txt}, com origem em {origem_txt}. Entretanto, não foram identificados elementos suficientes para uma análise conclusiva de compatibilidade do preço. "
        "Antes da formalização, a unidade responsável deve juntar justificativa de preço instruída com proposta atual e, sempre que possível, notas fiscais, contratos anteriores ou contratações similares, "
        "permitindo comparação objetiva e controle de razoabilidade."
    )


def matriz_riscos():
    return [
        ("Risco de enquadramento jurídico", "Utilização indevida da inexigibilidade sem comprovação plena de exclusividade ou consagração.", "Alta", "Alta", "Conferência da exclusividade, consagração, razão da escolha e justificativa de preço antes da autorização."),
        ("Risco documental", "Ausência, insuficiência ou vencimento de documentos de habilitação, regularidade fiscal, trabalhista ou representação.", "Média", "Alta", "Checklist documental, validação de autenticidade, vigência e saneamento antes da contratação."),
        ("Risco de preço", "Preço incompatível com parâmetros de mercado ou sem documentação comparativa suficiente.", "Média", "Alta", "Análise comparativa com proposta atual, notas fiscais e contratações similares, registrando diferenças de escopo."),
        ("Risco orçamentário", "Indisponibilidade de dotação, fonte inadequada ou insuficiência de saldo.", "Baixa", "Alta", "Validação da disponibilidade orçamentária antes da autorização e antes da formalização contratual."),
        ("Risco operacional", "Indefinição de data, local, horário, estrutura, camarim, acesso, segurança ou demais condições de execução.", "Média", "Média", "Definição prévia das condições de execução no TR e conferência pela fiscalização."),
        ("Risco climático", "Evento em local aberto sujeito a chuvas, ventos ou outras condições adversas.", "Média", "Média", "Previsão de plano de contingência, local alternativo, estrutura adequada ou regras de remarcação."),
        ("Risco de cancelamento ou no-show", "Impossibilidade de realização da apresentação por fato imputável ou não imputável às partes.", "Baixa", "Alta", "Cláusulas contratuais de comunicação, remarcação, restituição, penalidades e comprovação do fato impeditivo."),
        ("Risco de pagamento indevido", "Pagamento sem comprovação da execução, sem atesto ou com pendência documental.", "Baixa", "Alta", "Pagamento condicionado à nota fiscal, atesto, liquidação, regularidade exigível e comprovação da execução."),
    ]