import re, zipfile, tempfile
from pathlib import Path

try:
    from pypdf import PdfReader
except Exception:
    PdfReader = None

try:
    from docx import Document
except Exception:
    Document = None


def texto_pdf(path):
    if PdfReader is None:
        return ""
    try:
        return "\n".join([(p.extract_text() or "") for p in PdfReader(str(path)).pages])
    except Exception:
        return ""


def texto_docx(path):
    if Document is None:
        return ""
    try:
        return "\n".join([p.text for p in Document(str(path)).paragraphs])
    except Exception:
        return ""


def ler_texto(path):
    if path.suffix.lower() == ".pdf":
        txt = texto_pdf(path)
    elif path.suffix.lower() == ".docx":
        txt = texto_docx(path)
    else:
        txt = ""
    return path.name + "\n" + (txt or "")


def extrair_documentos(files):
    docs = []
    if not files:
        return docs
    with tempfile.TemporaryDirectory() as td:
        td = Path(td)
        for up in files:
            p = td / up.name
            p.write_bytes(up.getbuffer())
            if up.name.lower().endswith(".zip"):
                try:
                    ex = td / ("zip_" + p.stem)
                    ex.mkdir()
                    with zipfile.ZipFile(p, "r") as z:
                        z.extractall(ex)
                    for f in ex.rglob("*"):
                        if f.is_file():
                            txt = ler_texto(f)
                            docs.append({"nome": f.name, "texto": txt, "texto_lower": txt.lower()})
                except Exception:
                    docs.append({"nome": up.name, "texto": up.name, "texto_lower": up.name.lower()})
            else:
                txt = ler_texto(p)
                docs.append({"nome": up.name, "texto": txt, "texto_lower": txt.lower()})
    return docs




# Arquivos gerados pelo próprio sistema não podem comprovar documentação externa.
# Isso evita falso positivo quando o usuário reenvia o ZIP "Processo Completo".
ARQUIVOS_INTERNOS_GERADOS = [
    "01_dfd_base_requisicao", "01_requisicao", "02_dfd", "03_etp", "04_tr",
    "05_designacao_agente_etp", "06_designacao_agente_tr",
    "07_designacao_gestor_fiscal", "08_disponibilidade_orcamentaria",
    "09_certificacao_tr_padronizado", "relatorio_pendencias"
]

MARCADORES_DOCUMENTO_INTERNO = [
    "estudo técnico preliminar",
    "termo de referência",
    "documento de formalização de demanda",
    "termo de aprovação de estudo técnico preliminar",
    "termo de aprovação de termo de referência",
    "agente responsável pela elaboração do estudo técnico preliminar",
    "responsável pela elaboração do termo de referência",
    "build 1.0",
]


def nome_interno_gerado(nome):
    n = (nome or "").lower()
    n = n.replace("ç", "c").replace("ã", "a").replace("õ", "o").replace("é", "e").replace("í", "i")
    return any(m in n for m in ARQUIVOS_INTERNOS_GERADOS)


def documento_interno_gerado(doc):
    nome = doc.get("nome", "")
    tl = doc.get("texto_lower", "")
    if nome_interno_gerado(nome):
        return True
    # Só classifica como interno por conteúdo quando houver assinatura estrutural clara de peça gerada.
    fortes = [
        "termo de aprovação de estudo técnico preliminar",
        "termo de aprovação de termo de referência",
        "responsável pela elaboração do termo de referência",
        "agente responsável pela elaboração do estudo técnico preliminar",
        "documento de formalização de demanda",
    ]
    if any(f in tl for f in fortes) and nome.lower().endswith(".docx"):
        return True
    return False


def separar_documentos_externos(docs):
    externos = []
    internos = []
    for d in docs or []:
        if documento_interno_gerado(d):
            nd = dict(d)
            nd["ignorado_conferencia_externa"] = True
            internos.append(nd)
        else:
            externos.append(d)
    return externos, internos


TERMOS = {
    "requisição ao compras": ["requisição", "requisicao", "req.", "atende.net", "centro de custo", "valor total requisição"],
    "disponibilidade orçamentária": ["disponibilidade", "dotação", "dotacao", "código reduzido", "codigo reduzido", "reserva", "empenho", "ficha", "saldo orçamentário"],
    "designação do responsável pelo etp": ["responsável pelo etp", "responsavel pelo etp", "elaboração do etp", "designação", "designacao"],
    "designação do responsável pelo tr": ["responsável pelo tr", "responsavel pelo tr", "elaboração do termo de referência", "designação", "designacao"],
    "designação de gestor e fiscal": ["gestor", "fiscal", "gestor e fiscal", "fiscal do contrato"],
    "proposta comercial": ["proposta", "proposta comercial", "valor da proposta", "cachê", "cache"],
    "contrato/carta de exclusividade": ["exclusividade", "representante exclusivo", "empresário exclusivo", "empresario exclusivo"],
    "comprovação de consagração pública ou crítica especializada": ["release", "consagração", "consagracao", "notoriedade", "mídia", "midia", "youtube", "spotify", "instagram"],
    "justificativa de preço": ["justificativa de preço", "justificativa de preco", "compatibilidade do preço", "compatibilidade do preco", "razoabilidade", "notas fiscais", "nota fiscal", "nf-e", "danfe", "contratações similares", "contratacoes similares", "proposta detalhada", "proposta comercial", "valor da proposta"],
    "notas fiscais ou contratações similares": ["nota fiscal", "nf-e", "nfe", "danfe", "contratações similares", "contratacoes similares", "contrato anterior"],
    "cnpj": ["cnpj", "cadastro nacional da pessoa jurídica", "comprovante de inscrição", "receita federal"],
    "qsa": ["qsa", "quadro de sócios", "quadro de socios", "capital social"],
    "contrato social e aditivos": ["contrato social", "alteração contratual", "alteracao contratual", "junta comercial", "aditivo"],
    "certidão federal": ["certidão federal", "certidao federal", "receita federal", "pgfn", "tributos federais"],
    "certidão estadual": ["certidão estadual", "certidao estadual", "fazenda estadual", "sefaz"],
    "certidão municipal": ["certidão municipal", "certidao municipal", "tributos municipais", "fazenda municipal"],
    "fgts": ["fgts", "crf", "caixa econômica federal", "caixa economica federal"],
    "cndt / certidão trabalhista": ["cndt", "trabalhista", "justiça do trabalho", "justica do trabalho"],
    "sicaf ou declarações equivalentes": ["sicaf", "declaração", "declaracao", "consulta consolidada"],
    "pesquisa de preços": ["pesquisa de preços", "pesquisa de precos", "cotação", "cotacao", "orçamento", "orcamento"],
    "mapa comparativo": ["mapa comparativo", "comparativo de preços", "comparativo de precos"],
    "ci ou memorando de abertura do processo": ["ci", "comunicação interna", "comunicacao interna", "memorando", "abertura do processo", "solicitando a abertura", "secretaria demandante"],
    "justificativa da contratação e da adesão à ata": ["justificativa da contratação", "justificativa da contratacao", "justificativa da adesão", "justificativa da adesao", "justificativa para adesão", "justificativa para adesao", "vantajosidade da adesão", "vantajosidade da adesao"],
    "estudo técnico preliminar - etp, se aplicável": ["estudo técnico preliminar", "estudo tecnico preliminar", "etp"],
    "termo de referência": ["termo de referência", "termo de referencia"],
    "pesquisa de preços demonstrando vantajosidade": ["pesquisa de preços", "pesquisa de precos", "cotação", "cotacao", "orçamento", "orcamento", "vantajosidade", "preço de mercado", "preco de mercado"],
    "indicação ou reserva de dotação orçamentária": ["dotação", "dotacao", "reserva orçamentária", "reserva orcamentaria", "indicação orçamentária", "indicacao orcamentaria", "disponibilidade orçamentária", "disponibilidade orcamentaria"],
    "cópia do edital da licitação de origem": ["edital", "edital da licitação", "edital da licitacao", "licitação de origem", "licitacao de origem", "processo licitatório", "processo licitatorio"],
    "cópia da ata de registro de preços": ["ata de registro de preços", "ata registro de preços", "arp", "registro de preços"],
    "publicação da ata e da homologação": ["publicação", "publicacao", "homologação", "homologacao", "diário oficial", "diario oficial", "extrato da ata"],
    "termo de referência original da licitação": ["termo de referência original", "termo de referencia original", "termo de referência da licitação", "termo de referencia da licitacao"],
    "proposta vencedora da empresa": ["proposta vencedora", "proposta comercial", "empresa vencedora", "detentora da ata", "valor registrado"],
    "ofício ao órgão gerenciador solicitando autorização para adesão": ["ofício ao órgão gerenciador", "oficio ao orgao gerenciador", "solicitação de adesão", "solicitacao de adesao", "autorização para adesão", "autorizacao para adesao"],
    "resposta favorável do órgão gerenciador": ["resposta favorável", "resposta favoravel", "órgão gerenciador", "orgao gerenciador", "autoriza a adesão", "autoriza a adesao", "anuência do órgão", "anuencia do orgao"],
    "ofício à empresa detentora da ata": ["ofício à empresa", "oficio a empresa", "empresa detentora", "detentora da ata", "solicitação de aceite", "solicitacao de aceite"],
    "aceite formal da empresa": ["aceite formal", "aceite da empresa", "anuência da empresa", "anuencia da empresa", "concordância da empresa", "concordancia da empresa"],
    "consulta ceis/cnep e tcu": ["ceis", "cnep", "tcu", "consulta consolidada", "cadastro nacional de empresas inidôneas", "empresas inidoneas", "empresas punidas"],
    "parecer técnico": ["parecer técnico", "parecer tecnico", "manifestação técnica", "manifestacao tecnica", "análise técnica", "analise tecnica"],
    "parecer jurídico": ["parecer jurídico", "parecer juridico", "procuradoria", "jurídico", "juridico"],
    "autorização da autoridade competente": ["autorização", "autorizacao", "autoridade competente", "autorizo", "despacho autorizativo"],
    "empenho": ["empenho", "nota de empenho", "ne "],
    "contrato ou instrumento equivalente": ["contrato", "instrumento equivalente", "ordem de fornecimento", "ordem de serviço", "ordem de servico", "autorização de fornecimento", "autorizacao de fornecimento"],
    "publicação do extrato do contrato": ["publicação do extrato", "publicacao do extrato", "extrato do contrato", "diário oficial", "diario oficial"],
    "abertura de processo administrativo": ["abertura de processo", "processo administrativo", "autuação", "autuacao", "ci", "memorando", "formalização de processo", "formalizacao de processo"],
    "forma eletrônica ou justificativa para processo em papel": ["forma eletrônica", "forma eletronica", "processo eletrônico", "processo eletronico", "justificativa para processo em papel", "sistema eletrônico", "sistema eletronico"],
    "documento de formalização de demanda - dfd": ["documento de formalização de demanda", "documento de formalizacao de demanda", "dfd"],
    "certificação de compatibilidade com o plano de contratações anual": ["plano de contratações anual", "plano de contratacoes anual", "pca", "compatível com o pca", "compativel com o pca", "certificação pca", "certificacao pca"],
    "compatibilidade com as leis orçamentárias": ["lei orçamentária", "lei orcamentaria", "loa", "ldo", "ppa", "compatibilidade orçamentária", "compatibilidade orcamentaria", "dotação", "dotacao"],
    "estudo técnico preliminar - etp": ["estudo técnico preliminar", "estudo tecnico preliminar", "etp"],
    "etp com quantitativo demandado e local de entrega ou prestação do serviço": ["quantitativo demandado", "local de entrega", "local de prestação", "local de prestacao", "quantidade demandada", "local de execução", "local de execucao"],
    "justificativa da vantagem da adesão": ["justificativa da vantagem", "vantagem da adesão", "vantagem da adesao", "pertinência da adesão", "pertinencia da adesao", "vantajosidade da adesão", "vantajosidade da adesao"],
    "compatibilidade dos valores registrados com o mercado": ["compatíveis com o mercado", "compativeis com o mercado", "valores praticados pelo mercado", "pesquisa de preços", "pesquisa de precos", "vantajosidade", "valor de mercado"],
    "aceite do fornecedor ao pedido de adesão": ["aceite do fornecedor", "aceite formal da empresa", "aceite da empresa", "fornecedor aceitou", "anuência da empresa", "anuencia da empresa"],
    "aceitação da adesão pelo órgão ou entidade gerenciadora": ["aceitação da adesão pelo órgão", "aceitacao da adesao pelo orgao", "órgão gerenciador", "orgao gerenciador", "autoriza a adesão", "autoriza a adesao", "anuência do gerenciador", "anuencia do gerenciador"],
    "verificação se a ata é gerenciada por órgão ou entidade federal": ["órgão federal", "orgao federal", "administração pública federal", "administracao publica federal", "gerenciada por órgão federal", "gerenciada por orgao federal"],
    "observância do limite de 50% dos quantitativos registrados": ["limite de 50%", "cinquenta por cento", "50% dos quantitativos", "quantitativos registrados", "saldo da ata"],
    "formalização dentro do prazo de 90 dias da autorização do gerenciador": ["90 dias", "noventa dias", "prazo de 90", "autorização do gerenciador", "autorizacao do gerenciador", "vigência da ata", "vigencia da ata"],
    "formalização por contrato, empenho, autorização de compra ou instrumento hábil": ["contrato", "nota de empenho", "empenho", "autorização de compra", "autorizacao de compra", "instrumento hábil", "instrumento habil"],
    "instrumento firmado dentro da validade da ata de registro de preços": ["validade da ata", "vigência da ata", "vigencia da ata", "prazo de validade da ata", "dentro da validade"],
    "consultas sicaf, ceis, cnj e tcu do fornecedor": ["sicaf", "ceis", "cnj", "tcu", "consulta consolidada", "improbidade administrativa", "lista de inidôneos", "lista de inidoneos"],
    "consulta ao cadin": ["cadin", "cadastro informativo de créditos não quitados", "cadastro informativo de creditos nao quitados"],
    "consulta ao guia nacional de contratações sustentáveis": ["guia nacional de contratações sustentáveis", "guia nacional de contratacoes sustentaveis", "critérios de sustentabilidade", "criterios de sustentabilidade", "contratações sustentáveis", "contratacoes sustentaveis"],
}



def _buscar_por_termos(docs, termos):
    achados = []
    for d in docs:
        score = sum(1 for t in termos if t in d["texto_lower"])
        if score:
            achados.append((score, d["nome"]))
    return sorted(achados, reverse=True)


def _compor_justificativa_preco_artistica(docs_externos):
    """
    Em contratação artística por inexigibilidade, a justificativa de preço pode ser instruída
    por composição documental: Requisição/valor principal + proposta atual + notas fiscais
    ou contratações similares. Não exige arquivo isolado nomeado 'Justificativa de Preço'.
    """
    req = _buscar_por_termos(docs_externos, ["requisição", "requisicao", "req.", "valor total requisição", "valor total requisicao"])
    proposta = _buscar_por_termos(docs_externos, ["proposta", "proposta comercial", "proposta detalhada", "valor da proposta", "cachê", "cache"])
    similares = _buscar_por_termos(docs_externos, ["nota fiscal", "notas fiscais", "nf-e", "nfe", "danfe", "contratações similares", "contratacoes similares", "contrato anterior", "valor contratado"])

    # Documento específico continua aceito.
    explicita = _buscar_por_termos(docs_externos, ["justificativa de preço", "justificativa de preco", "compatibilidade do preço", "compatibilidade do preco", "razoabilidade do preço", "razoabilidade do preco"])
    if explicita:
        return {
            "ok": True,
            "arquivo": explicita[0][1],
            "score": explicita[0][0],
            "observacao": "Justificativa de preço localizada em documento específico."
        }

    if req and proposta and similares:
        arquivos = []
        for grupo in [req, proposta, similares]:
            if grupo and grupo[0][1] not in arquivos:
                arquivos.append(grupo[0][1])
        return {
            "ok": True,
            "arquivo": "Composição: " + " + ".join(arquivos[:3]),
            "score": 3,
            "observacao": "Justificativa de preço composta por Requisição ao Compras, proposta atual e notas fiscais/contratações similares, conforme padrão usado em processos artísticos similares."
        }

    if proposta and similares:
        arquivos = []
        for grupo in [proposta, similares]:
            if grupo and grupo[0][1] not in arquivos:
                arquivos.append(grupo[0][1])
        return {
            "ok": True,
            "arquivo": "Composição: " + " + ".join(arquivos[:2]),
            "score": 2,
            "observacao": "Justificativa de preço composta por proposta atual e notas fiscais/contratações similares."
        }

    return {
        "ok": False,
        "arquivo": "",
        "score": 0,
        "observacao": "Não localizada composição mínima para justificar preço."
    }


def conferir(docs, checklist):
    docs_externos, docs_internos = separar_documentos_externos(docs)
    res = []
    for item, crit in checklist:
        if item.lower().strip() == "justificativa de preço":
            comp = _compor_justificativa_preco_artistica(docs_externos)
            res.append({
                "item": item,
                "criticidade": crit,
                "status": "Encontrado" if comp["ok"] else "Pendente",
                "arquivo": comp["arquivo"],
                "score": comp["score"],
                "observacao": comp["observacao"],
                "internos_ignorados": [d["nome"] for d in docs_internos],
            })
            continue

        termos = TERMOS.get(item.lower(), item.lower().split())
        achados = _buscar_por_termos(docs_externos, termos)
        res.append({
            "item": item,
            "criticidade": crit,
            "status": "Encontrado" if achados else "Pendente",
            "arquivo": achados[0][1] if achados else "",
            "score": achados[0][0] if achados else 0,
            "internos_ignorados": [d["nome"] for d in docs_internos],
        })
    return res


def resumo(res):
    total = len(res)
    enc = sum(1 for r in res if r["status"] == "Encontrado")
    crit = sum(1 for r in res if r["status"] != "Encontrado" and r["criticidade"] == "critico")
    pct = round(enc / total * 100, 1) if total else 0
    sem = "🟢 Documentação externa apta para gerar DFD/ETP/TR" if crit == 0 and pct >= 85 else "🟡 Documentação externa com pendências" if crit <= 2 else "🔴 Documentação externa crítica"
    return {"total": total, "encontrados": enc, "pendentes": total - enc, "criticos": crit, "percentual": pct, "semaforo": sem}


def valores(texto):
    return list(dict.fromkeys(re.findall(r"R\$\s?\d{1,3}(?:\.\d{3})*,\d{2}", texto)))


def escolher_valor(docs):
    prioridade = [
        ("Requisição ao Compras", ["requisição", "requisicao", "req.", "valor total requisição"]),
        ("Proposta Comercial Atual", ["proposta comercial", "proposta"]),
        ("Termo de Referência", ["termo de referência", "termo de referencia"]),
        ("Disponibilidade Orçamentária", ["disponibilidade", "dotação", "dotacao", "reserva"]),
        ("Notas Fiscais / Contratos Anteriores", ["nota fiscal", "danfe", "nf-e", "contratações similares"]),
    ]
    regs = []
    for d in docs:
        vals = valores(d["texto"])
        if vals:
            regs.append({"arquivo": d["nome"], "valores": vals, "texto_lower": d["texto_lower"]})
    for origem, termos in prioridade:
        for r in regs:
            if any(t in r["texto_lower"] for t in termos):
                return {"valor": r["valores"][0], "origem": origem, "arquivo": r["arquivo"], "valores_localizados": regs}
    return {"valor": regs[0]["valores"][0] if regs else "", "origem": "Não localizado" if not regs else "Valor localizado sem origem prioritária", "arquivo": regs[0]["arquivo"] if regs else "", "valores_localizados": regs}



def first_match(patterns, text, flags=re.I):
    for pat in patterns:
        m = re.search(pat, text, flags)
        if m:
            return (m.group(1) if m.groups() else m.group(0)).strip()
    return ""


def linha_valor(rotulos, text):
    # Procura somente linhas iniciadas pelo rótulo. Evita falso positivo, por exemplo,
    # "Ação" dentro de "Administração".
    for rot in rotulos:
        pat = r"(?im)^\s*" + rot + r"\s*:?\s*([^\n\r]+)"
        m = re.search(pat, text, re.I)
        if m:
            return m.group(1).strip()
    return ""


def limpar_nome(nome):
    nome = (nome or "").strip()
    nome = re.sub(r"\s+", " ", nome)
    nome = nome.strip(" -:;")
    return nome


def extrair_assinaturas_requisicao(texto):
    """
    Extrai dados administrativos e assinaturas principalmente da Requisição ao Compras / Atende.Net.
    Regra: usar como sugestão automática, nunca como valor rígido. O usuário pode revisar no formulário.
    """
    emissor = first_match([
        r"Emitido\s+por\s*:\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]+?)(?:\s+Código|\s+Codigo|\n|$)",
        r"Emissor\s*:\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]+?)(?:\s+Telefone|\n|$)",
    ], texto)
    responsavel_req = first_match([
        r"Responsável\s*:\s*(?:\d+\s*-\s*)?([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]+?)(?:\n|$)",
        r"Responsavel\s*:\s*(?:\d+\s*-\s*)?([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]+?)(?:\n|$)",
    ], texto)

    autoridade = ""
    cargo = ""

    # Assinatura ao final da Requisição: linha de assinatura, nome e cargo.
    # Evita CPF e outros dados pessoais.
    m = re.search(
        r"_{5,}\s*\n\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]{5,})\s*\n\s*([Ss]ecret[aá]ri[ao][^\n\r]+)",
        texto,
        re.I,
    )
    if m:
        autoridade = limpar_nome(m.group(1))
        cargo = limpar_nome(m.group(2))

    # Alternativa: se houver bloco final sem underline reconhecível.
    if not autoridade:
        m2 = re.search(
            r"\n\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]{5,})\s*\n\s*([Ss]ecret[aá]ri[ao][^\n\r]+)",
            texto,
            re.I,
        )
        if m2:
            autoridade = limpar_nome(m2.group(1))
            cargo = limpar_nome(m2.group(2))

    secretaria_unidade = first_match([
        r"(?im)^\s*(?:Secretaria|Unidade\s+Requisitante|Unidade\s+Solicitante)\s*:?\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]{8,})",
        r"(?im)^\s*Centro\s+de\s+Custo\s*:?\s*(?:\d+\s*-\s*)?([A-ZÁÉÍÓÚÂÊÔÃÕÇ ]{8,})",
    ], texto)

    # Não usar a linha orçamentária "Unidade" como Secretaria, pois ela pode capturar trechos
    # quebrados como "s Tributárias". Para processos da Cultura/Turismo, aplicar fallback seguro.
    if not secretaria_unidade or "tribut" in secretaria_unidade.lower() or len(secretaria_unidade.strip()) < 12:
        if "cultura" in texto.lower() or "turismo" in texto.lower() or "programa cultura em movimento" in texto.lower():
            secretaria_unidade = "Secretaria Municipal de Cultura e Turismo"

    return {
        "emissor_requisicao": limpar_nome(emissor),
        "responsavel_requisicao": limpar_nome(responsavel_req),
        "autoridade_assinante": limpar_nome(autoridade),
        "cargo_autoridade": limpar_nome(cargo),
        "secretaria_extraida": limpar_nome(secretaria_unidade),
    }




def normalizar_data_iso_ou_br(data):
    if not data:
        return ""
    s = str(data).strip()
    # dd/mm/yyyy
    m = re.search(r"\b([0-3]?\d)[/.-]([01]?\d)[/.-]((?:20)?\d{2})\b", s)
    if not m:
        return s
    d, mo, y = m.groups()
    if len(y) == 2:
        y = "20" + y
    return f"{int(d):02d}/{int(mo):02d}/{int(y):04d}"


def extrair_data_evento(texto):
    """
    Extrai data provável do evento. Evita usar datas de emissão/certidões quando possível.
    Prioridade:
    1) rótulos explícitos: data do evento, realização, apresentação;
    2) datas próximas das palavras evento/show/apresentação;
    3) datas no nome de proposta, quando houver padrão dd-mm-aa.
    """
    if not texto:
        return ""

    padroes_rotulo = [
        r"(?i)data\s+do\s+evento\s*:?\s*([0-3]?\d[/.-][01]?\d[/.-](?:20)?\d{2})",
        r"(?i)data\s+da\s+apresenta[cç][aã]o\s*:?\s*([0-3]?\d[/.-][01]?\d[/.-](?:20)?\d{2})",
        r"(?i)realiza(?:do|ção|cao|r-se)?\s*(?:em|no\s+dia)?\s*:?\s*([0-3]?\d[/.-][01]?\d[/.-](?:20)?\d{2})",
        r"(?i)apresenta[cç][aã]o.*?(?:em|dia)\s*([0-3]?\d[/.-][01]?\d[/.-](?:20)?\d{2})",
        r"(?i)show.*?(?:em|dia)\s*([0-3]?\d[/.-][01]?\d[/.-](?:20)?\d{2})",
    ]
    for pat in padroes_rotulo:
        m = re.search(pat, texto, flags=re.S)
        if m:
            return normalizar_data_iso_ou_br(m.group(1))

    # Nome de arquivo/proposta costuma vir como "Sumaré - SP 26-07-26"
    m = re.search(r"(?i)(?:proposta|sumar[eé]|evento|show|apresenta[cç][aã]o)[^\n\r]{0,80}\b([0-3]?\d[-./][01]?\d[-./](?:20)?\d{2})\b", texto)
    if m:
        return normalizar_data_iso_ou_br(m.group(1))

    return ""


def extrair_dados_processuais(texto):
    processo = first_match([
        r"Processo\s+Digital\s*:?\s*([0-9]{1,8}/[0-9]{4})",
        r"Processo\s+Administrativo\s*(?:n[º°.]|n\.)?\s*:?\s*([0-9]{1,8}/[0-9]{4})",
        r"Processo\s+DLC\s*(?:n[º°.]|n\.)?\s*:?\s*([0-9]{1,8}/[0-9]{4})",
        r"PROCESSO\s+DLC\s*(?:N\.|n\.)?\s*([0-9]{1,8}/[0-9]{4})",
    ], texto)
    req = first_match([
        r"Req\.\s*N[º°.]?\s*:?\s*([0-9]{1,8}/[0-9]{4})",
        r"Requisição\s*(?:ao\s+Compras)?\s*(?:n[º°.]|n\.)?\s*:?\s*([0-9]{1,8}/[0-9]{4})",
    ], texto)
    data_emissao = first_match([
        r"Emitida\s+em\s*:?\s*([0-9]{2}/[0-9]{2}/[0-9]{4})",
        r"Data\s+da\s+Requisi[cç][aã]o\s*:?\s*([0-9]{2}/[0-9]{2}/[0-9]{4})",
        r"Data\s+de\s+Emiss[aã]o\s*:?\s*([0-9]{2}/[0-9]{2}/[0-9]{4})",
    ], texto)
    data_emissao = normalizar_data_iso_ou_br(data_emissao)
    data_evento = extrair_data_evento(texto)
    codigo_reduzido = linha_valor([r"Código\s+Reduzido", r"Codigo\s+Reduzido"], texto)
    # Priorizar captura direta e completa da Funcional Programática, pois PDFs do Atende.Net podem quebrar linhas.
    funcional = first_match([
        r"Funcional\s+Program[áa]tica\s*:?\s*([0-9]{4}\.[0-9]{4}\.[0-9]{4}\.[0-9]{4})",
        r"Funcional\s+Program[áa]tica\s*:?\s*([0-9]{4}\.[0-9]{4}\.[0-9]{4})",
    ], texto) or linha_valor([r"Funcional\s+Programática", r"Funcional\s+Programatica"], texto)
    orgao = linha_valor([r"Órgão", r"Orgao"], texto)
    unidade = linha_valor([r"Unidade"], texto)
    acao = linha_valor([r"Ação", r"Acao"], texto)
    subelemento = linha_valor([r"Subelemento"], texto)
    vinculo = linha_valor([r"Vínculo", r"Vinculo"], texto)
    valor_total_req = first_match([r"Valor\s+Total\s+Requisição\s*:?\s*(R\$\s?\d{1,3}(?:\.\d{3})*,\d{2})"], texto)
    cond_pag = linha_valor([r"Condição\s+de\s+Pagamento", r"Condicao\s+de\s+Pagamento"], texto)
    local_entrega = linha_valor([r"Local\s+de\s+Entrega"], texto)
    # Alguns PDFs do Atende.Net trazem a dotação compactada em uma única linha; usar apenas se não houver código completo.
    if not funcional:
        m_dot = re.search(r"Dotação\s+\d+\s+-\s+([0-9]{2}\.[0-9]{3}\.[0-9]{4})", texto, re.I)
        if m_dot:
            funcional = m_dot.group(1)

    dotacao_partes = []
    for label, val in [
        ("Código Reduzido", codigo_reduzido),
        ("Funcional Programática", funcional),
        ("Órgão", orgao),
        ("Unidade", unidade),
        ("Ação", acao),
        ("Subelemento", subelemento),
        ("Vínculo", vinculo),
    ]:
        if val:
            dotacao_partes.append(f"{label}: {val}")
    return {
        "processo_numero": processo,
        "numero_requisicao": req,
        "data_emissao_requisicao": data_emissao,
        "data_evento_extraida": data_evento,
        "codigo_reduzido": codigo_reduzido,
        "funcional_programatica": funcional,
        "orgao": orgao,
        "unidade_orcamentaria": unidade,
        "acao": acao,
        "subelemento": subelemento,
        "vinculo": vinculo,
        "valor_total_requisicao": valor_total_req,
        "condicao_pagamento": cond_pag,
        "local_entrega": local_entrega,
        "dotacao_texto": "; ".join(dotacao_partes),
    }


def dados_extraidos(docs):
    txt = "\n".join(d["texto"] for d in docs)
    low = txt.lower()
    artista = ""
    artistas_conhecidos = [
        "Cassiane",
        "Luan Pereira",
        "Gabriela Rocha",
        "Thiago Brado",
        "Sarah Farias",
        "Sara Farias",
        "Aline Barros",
        "Bruna Karla",
        "Fernanda Brum",
    ]
    for a in artistas_conhecidos:
        if a.lower() in low:
            artista = "Sarah Farias" if a.lower() == "sara farias" else a

    # Fallback por padrões comuns em proposta, exclusividade e release.
    if not artista:
        padroes_artista = [
            r"(?i)(?:artista|cantor|cantora|show|apresenta[cç][aã]o)\s+(?:de|do|da)?\s*([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç]+(?:\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç]+){0,3})",
            r"(?i)proposta\s+(?:comercial\s+)?(?:para\s+)?([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç]+(?:\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç]+){0,3})",
        ]
        for pat in padroes_artista:
            m = re.search(pat, txt)
            if m:
                cand = m.group(1).strip()
                if cand.lower() not in ["aniversário da cidade", "aniversario da cidade", "sumaré", "municipio"]:
                    artista = cand
                    break
    cnpj = re.search(r"\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}", txt)
    evento = ""
    for e in ["aniversário da cidade", "aniversario da cidade", "natal para jesus", "arraiá", "arraia", "natal"]:
        if e in low:
            evento = e.title().replace("Da", "da").replace("Para", "para")
            break
    val = escolher_valor(docs)
    proc = extrair_dados_processuais(txt)
    assinaturas = extrair_assinaturas_requisicao(txt)
    valor_principal = proc.get("valor_total_requisicao") or val["valor"]
    origem_valor = "Requisição ao Compras" if proc.get("valor_total_requisicao") else val["origem"]
    objeto = ""
    if artista and evento:
        objeto = f"Contratação de serviços artísticos para a realização de apresentação ao vivo da cantora {artista}, no evento “{evento}”, a ser realizado no Município de Sumaré/SP"
    elif artista:
        objeto = f"Contratação de serviços artísticos para a realização de apresentação ao vivo da cantora {artista}"
    extra = {
        "artista": artista,
        "cnpj": cnpj.group(0) if cnpj else "",
        "evento": evento,
        "valor_principal": valor_principal,
        "origem_valor": origem_valor,
        "arquivo_valor": val["arquivo"],
        "valores_localizados": val["valores_localizados"],
        "objeto_sugerido": objeto,
    }
    extra.update(proc)
    extra.update(assinaturas)
    return extra


def internos_ignorados(docs):
    _, internos = separar_documentos_externos(docs)
    return [d["nome"] for d in internos]
