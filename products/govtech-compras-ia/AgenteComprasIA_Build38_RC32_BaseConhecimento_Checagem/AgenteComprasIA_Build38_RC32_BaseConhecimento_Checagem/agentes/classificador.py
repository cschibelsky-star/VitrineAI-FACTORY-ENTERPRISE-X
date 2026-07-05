def classificar_demanda(descricao, secretaria="", valor_estimado=""):
    texto = (descricao or "").lower()
    artistica = any(p in texto for p in [
        "show", "cantor", "cantora", "banda", "artista", "apresentação",
        "musical", "cassiane", "luan pereira", "gabriela rocha", "thiago brado"
    ])
    estrutura = any(p in texto for p in ["palco", "som", "sonorização", "iluminação", "led", "gerador", "estrutura"])
    adesao_ata = any(p in texto for p in [
        "adesão à ata", "adesao a ata", "adesão a ata", "adesao à ata",
        "ata de registro de preços", "ata registro de preços", "arp",
        "carona", "órgão gerenciador", "orgao gerenciador", "detentora da ata",
        "empresa detentora", "registro de preços"
    ])

    documentos_gerados_sistema = [
        ("Documento de Formalização da Demanda - DFD", "sistema"),
        ("Estudo Técnico Preliminar - ETP", "sistema"),
        ("Termo de Referência - TR", "sistema"),
    ]

    if adesao_ata:
        tipo = "Adesão à Ata de Registro de Preços"
        modalidade = "Adesão à Ata de Registro de Preços"
        fundamento = (
            "Processo administrativo de adesão à Ata de Registro de Preços, com verificação formal baseada "
            "na Lista de Verificação da AGU/Câmara Nacional de Modelos de Licitações e Contratos para adesão "
            "à ata de registro de preços, Lei nº 14.133/2021, atualização SET/2024, sem prejuízo dos modelos "
            "e exigências municipais aplicáveis."
        )
        objeto = "Abertura e instrução de processo administrativo de adesão à Ata de Registro de Preços"
        checklist = [
            ("Abertura de processo administrativo", "critico"),
            ("Forma eletrônica ou justificativa para processo em papel", "atencao"),
            ("Documento de Formalização de Demanda - DFD", "critico"),
            ("Certificação de compatibilidade com o Plano de Contratações Anual", "atencao"),
            ("Compatibilidade com as leis orçamentárias", "critico"),
            ("Estudo Técnico Preliminar - ETP", "atencao"),
            ("ETP com quantitativo demandado e local de entrega ou prestação do serviço", "atencao"),
            ("Justificativa da vantagem da adesão", "critico"),
            ("Compatibilidade dos valores registrados com o mercado", "critico"),
            ("Aceite do fornecedor ao pedido de adesão", "critico"),
            ("Aceitação da adesão pelo órgão ou entidade gerenciadora", "critico"),
            ("Verificação se a ata é gerenciada por órgão ou entidade federal", "atencao"),
            ("Observância do limite de 50% dos quantitativos registrados", "critico"),
            ("Formalização dentro do prazo de 90 dias da autorização do gerenciador", "critico"),
            ("Formalização por contrato, empenho, autorização de compra ou instrumento hábil", "critico"),
            ("Instrumento firmado dentro da validade da Ata de Registro de Preços", "critico"),
            ("Consultas SICAF, CEIS, CNJ e TCU do fornecedor", "critico"),
            ("Consulta ao CADIN", "atencao"),
            ("Consulta ao Guia Nacional de Contratações Sustentáveis", "atencao"),
            ("Cópia do Edital da licitação de origem", "critico"),
            ("Cópia da Ata de Registro de Preços", "critico"),
            ("Publicação da Ata e da homologação", "critico"),
            ("Termo de Referência original da licitação", "atencao"),
            ("Proposta vencedora da empresa", "critico"),
            ("Parecer jurídico", "critico"),
            ("Autorização da autoridade competente", "critico"),
            ("Publicação do extrato do contrato", "atencao"),
        ]
    elif artistica:
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
    if modalidade == "Adesão à Ata de Registro de Preços":
        pendencias += [
            "Comprovar vantajosidade da adesão por pesquisa de preços atualizada.",
            "Juntar autorização do órgão gerenciador e aceite formal da empresa detentora da Ata.",
            "Conferir vigência da Ata, saldo disponível e compatibilidade entre objeto pretendido e item registrado.",
            "Submeter à análise jurídica antes da autorização final da autoridade competente.",
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