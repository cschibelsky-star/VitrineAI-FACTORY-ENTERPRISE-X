def classificar_demanda(descricao, secretaria="", valor_estimado=""):
    texto = (descricao or "").lower()
    artistica = any(p in texto for p in [
        "show", "cantor", "cantora", "banda", "artista", "apresentação",
        "musical", "cassiane", "luan pereira", "gabriela rocha", "thiago brado"
    ])
    estrutura = any(p in texto for p in ["palco", "som", "sonorização", "iluminação", "led", "gerador", "estrutura"])

    documentos_gerados_sistema = [
        ("Documento de Formalização da Demanda - DFD", "sistema"),
        ("Estudo Técnico Preliminar - ETP", "sistema"),
        ("Termo de Referência - TR", "sistema"),
    ]

    if artistica:
        tipo = "Contratação Artística"
        modalidade = "Inexigibilidade"
        fundamento = (
            "Art. 74, inciso II, da Lei Federal nº 14.133/2021, condicionado à comprovação "
            "de exclusividade, consagração pública/crítica especializada e justificativa/composição de preço."
        )
        objeto = "Contratação de serviços artísticos para realização de apresentação ao vivo"
        checklist = [
            ("Requisição ao Compras", "critico"),
            ("Disponibilidade Orçamentária", "critico"),
            ("Proposta Comercial", "critico"),
            ("Contrato/Carta de Exclusividade", "critico"),
            ("Comprovação de Consagração Pública ou Crítica Especializada", "critico"),
            ("Justificativa de Preço", "critico"),
            ("Notas Fiscais ou Contratações Similares", "critico"),
            ("CNPJ", "critico"),
            ("QSA", "atencao"),
            ("Contrato Social e Aditivos", "atencao"),
            ("Certidão Federal", "critico"),
            ("Certidão Estadual", "atencao"),
            ("Certidão Municipal", "atencao"),
            ("FGTS", "critico"),
            ("CNDT / Certidão Trabalhista", "critico"),
            ("SICAF ou Declarações equivalentes", "atencao"),
        ]
    elif estrutura:
        tipo = "Estrutura de Evento"
        modalidade = "Pregão Eletrônico ou Dispensa, conforme valor e enquadramento"
        fundamento = "Lei Federal nº 14.133/2021 e regulamentos municipais aplicáveis."
        objeto = "Contratação de estrutura e serviços de apoio para evento"
        checklist = [
            ("Requisição ao Compras", "critico"),
            ("Disponibilidade Orçamentária", "critico"),
            ("Pesquisa de Preços", "critico"),
            ("Mapa Comparativo", "atencao"),
            ("Designação de Gestor e Fiscal", "critico"),
        ]
    else:
        tipo = "Contratação a Classificar"
        modalidade = "Pendente de análise"
        fundamento = "Pendente de análise técnica pela unidade requisitante."
        objeto = "Objeto a classificar conforme necessidade informada"
        checklist = [
            ("Requisição ao Compras", "critico"),
            ("Pesquisa de preços ou justificativa", "critico"),
            ("Disponibilidade Orçamentária", "critico"),
        ]

    pendencias = []
    if not valor_estimado:
        pendencias.append("Valor estimado não informado; complementar após proposta ou requisição.")
    if modalidade == "Inexigibilidade":
        pendencias += [
            "Confirmar exclusividade formal do artista/representante.",
            "Juntar comprovação de consagração pública ou crítica especializada.",
            "Instruir justificativa/composição de preço com proposta atual, notas fiscais ou contratações similares.",
        ]

    return {
        "tipo": tipo,
        "categoria": tipo,
        "modalidade": modalidade,
        "fundamentacao": fundamento,
        "objeto_resumido": objeto,
        "secretaria": secretaria or "Secretaria requisitante",
        "checklist": checklist,
        "documentos_necessarios": [i[0] for i in checklist],
        "documentos_gerados_sistema": documentos_gerados_sistema,
        "pendencias": pendencias,
    }