import sys
import sqlite3
import zipfile
import re
import unicodedata
from pathlib import Path
from datetime import datetime

import streamlit as st

ROOT = Path(__file__).resolve().parents[1]
sys.path.append(str(ROOT))

from agentes.classificador import classificar_demanda
from agentes.documentos import gerar_requisicao, gerar_dfd, gerar_etp, gerar_tr, gerar_processo_completo
from agentes.analise_documental import extrair_documentos, conferir, resumo, dados_extraidos, internos_ignorados
from agentes.evidencias import montar_evidencias
from agentes.relatorio import gerar_relatorio_pendencias
from agentes.pdf_export import gerar_pdfs_documentos, converter_docx_para_pdf
from agentes.base_homologada import adicionar_processo_homologado, buscar_caso_similar, carregar_casos, resumo_caso
from agentes.revisor_ia import revisar_processo_com_ia

DB_DIR = ROOT / "banco"
DB = DB_DIR / "compras.db"
PROC = ROOT / "processos"

# Garante criação das pastas mesmo quando o app roda pelo Streamlit no Windows,
# a partir de Downloads, Desktop, OneDrive ou outro diretório sem contexto fixo.
DB_DIR.mkdir(parents=True, exist_ok=True)
PROC.mkdir(parents=True, exist_ok=True)


def get_conn():
    DB_DIR.mkdir(parents=True, exist_ok=True)
    PROC.mkdir(parents=True, exist_ok=True)
    return sqlite3.connect(str(DB), timeout=30)


def init_db():
    try:
        conn = get_conn()
        cur = conn.cursor()
        cur.execute(
            "CREATE TABLE IF NOT EXISTS processos (id INTEGER PRIMARY KEY AUTOINCREMENT, objeto TEXT, secretaria TEXT, modalidade TEXT, data_criacao TEXT)"
        )
        conn.commit()
        conn.close()
    except sqlite3.OperationalError as e:
        st.error("Não foi possível abrir/criar o banco local SQLite.")
        st.info(f"Caminho esperado do banco: {DB}")
        st.warning("Extraia o ZIP completo para uma pasta local simples, por exemplo C:\\AgenteComprasIA, e abra novamente pelo arquivo ABRIR_AGENTE_COMPRAS.bat.")
        raise e



def nome_seguro_arquivo(texto, limite=60):
    texto = str(texto or "").strip()
    if not texto:
        return "Compra"
    texto = unicodedata.normalize("NFKD", texto)
    texto = "".join(c for c in texto if not unicodedata.combining(c))
    texto = re.sub(r"[^A-Za-z0-9]+", "_", texto)
    texto = re.sub(r"_+", "_", texto).strip("_")
    if not texto:
        return "Compra"
    return texto[:limite].strip("_")


def nome_compra_processo(dados):
    # Prioridade: artista/fornecedor principal. Para contratação artística, esse é o nome mais útil.
    artista = (dados or {}).get("artista")
    if artista:
        return nome_seguro_arquivo(artista)

    # Fallback: tenta extrair um trecho curto do objeto.
    desc = (dados or {}).get("descricao", "")
    padroes = [
        r"cantor(?:a)?\s+([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç\s]+?)(?:,| no evento| para|$)",
        r"artista\s+([A-ZÁÉÍÓÚÂÊÔÃÕÇ][A-Za-zÁÉÍÓÚÂÊÔÃÕÇáéíóúâêôãõç\s]+?)(?:,| no evento| para|$)",
    ]
    for pat in padroes:
        m = re.search(pat, desc, flags=re.I)
        if m:
            return nome_seguro_arquivo(m.group(1))

    # Último fallback: objeto resumido.
    return nome_seguro_arquivo(desc[:50] or "Compra")


def pasta_processo(pid, dados=None, completo=False):
    nome = nome_compra_processo(dados or {})
    sufixo = "_Completo" if completo else ""
    return PROC / f"Processo_{pid:04d}_{nome}{sufixo}"


def novo_processo(dados, classificacao):
    if "pid" in st.session_state:
        return st.session_state["pid"]

    conn = get_conn()
    cur = conn.cursor()
    cur.execute(
        "INSERT INTO processos (objeto, secretaria, modalidade, data_criacao) VALUES (?, ?, ?, ?)",
        (dados.get("descricao", ""), dados.get("secretaria", ""), classificacao.get("modalidade", ""), datetime.now().isoformat()),
    )
    pid = cur.lastrowid
    conn.commit()
    conn.close()
    st.session_state["pid"] = pid
    pasta_processo(pid, dados).mkdir(parents=True, exist_ok=True)
    return pid


def baixar_doc(tipo, func, dados, classificacao, extraidos=None):
    pid = novo_processo(dados, classificacao)
    pasta = pasta_processo(pid, dados)
    arquivo = func(pid, dados, classificacao, pasta, extraidos)

    st.success(f"{tipo} gerado em DOCX: {arquivo.name}")

    with open(arquivo, "rb") as f:
        st.download_button(f"Baixar {tipo}.docx", f, file_name=arquivo.name)

    # Também tenta gerar PDF para documentos individuais.
    pasta_pdf = pasta / "PDF"
    pdf, erros = converter_docx_para_pdf(arquivo, pasta_pdf)
    if pdf:
        st.success(f"{tipo} também foi convertido para PDF.")
        with open(pdf, "rb") as f:
            st.download_button(f"Baixar {tipo}.pdf", f, file_name=pdf.name)
    else:
        st.info(
            f"{tipo} foi gerado em DOCX. Para gerar PDF automaticamente, este computador precisa ter LibreOffice ou Microsoft Word/docx2pdf."
        )



def zipar_pasta(pasta, nome_zip=None):
    pasta = Path(pasta)
    zip_saida = pasta / (nome_zip or f"{pasta.name}.zip")
    with zipfile.ZipFile(zip_saida, "w", zipfile.ZIP_DEFLATED) as z:
        for f in pasta.rglob("*"):
            if f == zip_saida:
                continue
            if f.is_file():
                z.write(f, f.relative_to(pasta))
    return zip_saida


def baixar_processo_completo(dados, classificacao, extraidos=None):
    pid = novo_processo(dados, classificacao)
    pasta = pasta_processo(pid, dados, completo=True)
    pasta.mkdir(parents=True, exist_ok=True)
    arquivos = gerar_processo_completo(pid, dados, classificacao, pasta, extraidos)

    # Organiza DOCX em pasta própria e gera também a versão em PDF.
    pasta_docx = pasta / "DOCX"
    pasta_docx.mkdir(parents=True, exist_ok=True)
    arquivos_docx = []
    for arq in arquivos:
        destino = pasta_docx / arq.name
        if Path(arq) != destino:
            try:
                destino.write_bytes(Path(arq).read_bytes())
            except Exception:
                destino = Path(arq)
        arquivos_docx.append(destino)

    # A conversão preserva cabeçalho, rodapé, tabelas e marca d'água quando há LibreOffice ou Word disponível.
    pasta_pdf = pasta / "PDF"
    pdfs, falhas_pdf, erros_pdf, bat_pdf = gerar_pdfs_documentos(
        arquivos_docx, pasta_pdf, pasta_processo=pasta, pasta_docx=pasta_docx
    )

    aviso_pdf = None
    if falhas_pdf:
        aviso_pdf = pasta / "AVISO_GERACAO_PDF.txt"
        detalhes = []
        for nome, erros in erros_pdf.items():
            detalhes.append(f"- {nome}: " + (" | ".join(erros[-2:]) if erros else "sem conversor disponível"))
        aviso_pdf.write_text(
            "Alguns PDFs não puderam ser gerados neste computador.\n\n"
            "O sistema tentou localizar LibreOffice e Microsoft Word/docx2pdf.\n"
            "Para geração automática de PDF, instale o LibreOffice ou mantenha o Microsoft Word disponível.\n\n"
            "Foi criado o arquivo CONVERTER_PARA_PDF.bat dentro da pasta do processo.\n"
            "Após instalar o LibreOffice, execute esse arquivo para converter os DOCX em PDF.\n\n"
            "Documentos sem PDF: " + ", ".join(falhas_pdf) + "\n\n"
            "Detalhes técnicos:\n" + "\n".join(detalhes) + "\n",
            encoding="utf-8",
        )

    zip_saida = pasta / f"{pasta.name}.zip"
    with zipfile.ZipFile(zip_saida, "w", zipfile.ZIP_DEFLATED) as z:
        for arq in arquivos_docx:
            z.write(arq, f"DOCX/{arq.name}")
        for pdf in pdfs:
            z.write(pdf, f"PDF/{pdf.name}")
        if bat_pdf:
            z.write(bat_pdf, bat_pdf.name)
        if aviso_pdf:
            z.write(aviso_pdf, aviso_pdf.name)

    st.session_state["ultimo_processo_pasta"] = str(pasta)
    st.session_state["ultimo_processo_zip"] = str(zip_saida)
    st.session_state["ultimo_processo_dados"] = dict(dados)
    st.session_state["ultimo_processo_classificacao"] = dict(classificacao)

    if pdfs and not falhas_pdf:
        st.success("Processo completo gerado com DOCX e PDF.")
    elif pdfs and falhas_pdf:
        st.warning("Processo completo gerado com DOCX e PDFs parciais. Alguns arquivos não converteram neste computador.")
    else:
        st.warning("Processo completo gerado em DOCX. A conversão para PDF depende de LibreOffice ou Microsoft Word neste computador.")
        if bat_pdf:
            st.info("Incluí no ZIP o arquivo CONVERTER_PARA_PDF.bat para converter depois que o LibreOffice estiver instalado.")
    with open(zip_saida, "rb") as f:
        st.download_button("Baixar Processo Completo (.zip)", f, file_name=zip_saida.name)




MESES_PT_APP = {
    1: "Janeiro", 2: "Fevereiro", 3: "Março", 4: "Abril",
    5: "Maio", 6: "Junho", 7: "Julho", 8: "Agosto",
    9: "Setembro", 10: "Outubro", 11: "Novembro", 12: "Dezembro",
}


def normalizar_data_br_app(data):
    if not data:
        return ""
    s = str(data).strip()
    m = re.search(r"\b([0-3]?\d)[/.-]([01]?\d)[/.-]((?:20)?\d{2})\b", s)
    if not m:
        return s
    d, mo, y = m.groups()
    if len(y) == 2:
        y = "20" + y
    return f"{int(d):02d}/{int(mo):02d}/{int(y):04d}"


def data_extenso_app(data):
    s = normalizar_data_br_app(data)
    m = re.match(r"^([0-3]\d)/([01]\d)/(20\d{2})$", s or "")
    if not m:
        return s
    d, mo, y = m.groups()
    return f"{int(d)} de {MESES_PT_APP.get(int(mo), mo)} de {y}"


def data_atual_extenso_app():
    hoje = datetime.now()
    return f"{hoje.day} de {MESES_PT_APP.get(hoje.month, hoje.month)} de {hoje.year}"


def data_documento_default(ext):
    return data_extenso_app(ext.get("data_emissao_requisicao")) or data_atual_extenso_app()


def data_evento_default(ext):
    return normalizar_data_br_app(ext.get("data_evento_extraida"))


def data_conclusao_default(ext):
    return data_evento_default(ext)


def secretaria_default(ext):
    sec = (ext.get("secretaria_extraida") or "").strip()
    if not sec or "tribut" in sec.lower() or len(sec) < 12:
        return "Secretaria Municipal de Cultura e Turismo"
    return sec



def main():
    st.set_page_config(page_title="Agente Compras IA - Build 1.0 RC29", layout="wide")
    init_db()

    st.title("Agente Compras IA — Build 1.0 RC29")
    st.caption("Geração DOCX + PDF quando houver conversor local; mantém marca d'água, cabeçalho, rodapé e layout institucional")

    aba1, aba2, aba3 = st.tabs([
        "1. Upload / Conferência / Evidências",
        "2. Dados Complementares",
        "3. Geração Técnica"
    ])

    with aba1:
        st.header("Upload dos Documentos Externos")
        st.info("DFD, ETP e TR não são documentos de entrada. Eles serão gerados pelo sistema com base nos anexos e dados complementares.")

        tipo = st.selectbox("Tipo de contratação", ["Contratação artística", "Estrutura de evento", "Geral"])
        arquivos = st.file_uploader("Arquivos do processo", accept_multiple_files=True, type=["pdf", "docx", "zip", "xlsx", "xls"])

        if st.button("Analisar Documentação Externa", type="primary"):
            base = "Contratação de show artístico" if tipo == "Contratação artística" else "Contratação de palco e som" if tipo == "Estrutura de evento" else "Contratação geral"
            classificacao = classificar_demanda(base, "Secretaria Municipal de Cultura e Turismo", "1")
            docs = extrair_documentos(arquivos)
            ignorados = internos_ignorados(docs)
            if ignorados:
                st.warning(
                    "Documentos gerados pelo próprio sistema foram ignorados na conferência externa: "
                    + ", ".join(ignorados[:12])
                    + ("..." if len(ignorados) > 12 else "")
                )
            resultados = conferir(docs, classificacao["checklist"])
            res = resumo(resultados)
            ext = dados_extraidos(docs)
            evidencias = montar_evidencias(resultados)
            ext["evidencias"] = evidencias

            st.session_state["classificacao"] = classificacao
            st.session_state["resultados"] = resultados
            st.session_state["resumo"] = res
            st.session_state["extraidos"] = ext
            st.session_state["evidencias"] = evidencias
            st.session_state.pop("pid", None)

        if "resultados" in st.session_state:
            res = st.session_state["resumo"]
            resultados = st.session_state["resultados"]
            ext = st.session_state["extraidos"]
            evidencias = st.session_state["evidencias"]
            classificacao = st.session_state["classificacao"]

            st.subheader("Resumo da Documentação Externa")
            c1, c2, c3, c4 = st.columns(4)
            c1.metric("Completude externa", f"{res['percentual']}%")
            c2.metric("Encontrados", res["encontrados"])
            c3.metric("Pendentes", res["pendentes"])
            c4.metric("Críticos externos", res["criticos"])
            st.progress(int(res["percentual"]))
            st.info(res["semaforo"])

            st.subheader("Evidências técnicas do processo")
            ecols = st.columns(4)
            itens_evid = [
                ("Exclusividade", evidencias.get("exclusividade", {}).get("ok")),
                ("Consagração", evidencias.get("consagracao", {}).get("ok")),
                ("Justificativa de Preço", evidencias.get("justificativa_preco", {}).get("ok")),
                ("Regularidade Fiscal", evidencias.get("regularidade_fiscal", {}).get("ok")),
                ("Regularidade Jurídica", evidencias.get("regularidade_juridica", {}).get("ok")),
                ("Dotação/Orçamento", evidencias.get("disponibilidade_orcamentaria", {}).get("ok")),
                ("Gestor/Fiscal", evidencias.get("gestor_fiscal", {}).get("ok")),
                ("Certidões", evidencias.get("certidoes", {}).get("ok")),
            ]
            for idx, (nome, ok) in enumerate(itens_evid):
                with ecols[idx % 4]:
                    if ok:
                        st.success("✓ " + nome)
                    else:
                        st.warning("○ " + nome)

            st.subheader("Dados Extraídos")
            a, b, c = st.columns(3)
            a.write(f"**Artista:** {ext.get('artista') or 'Não localizado'}")
            b.write(f"**CNPJ:** {ext.get('cnpj') or 'Não localizado'}")
            c.write(f"**Evento:** {ext.get('evento') or 'Não localizado'}")
            st.write(f"**Valor principal:** {ext.get('valor_principal') or 'Não localizado'}")
            st.write(f"**Origem do valor:** {ext.get('origem_valor') or 'Não localizada'}")
            st.write(f"**Arquivo do valor:** {ext.get('arquivo_valor') or 'Não localizado'}")
            st.write(f"**Processo:** {ext.get('processo_numero') or 'Não localizado'}")
            st.write(f"**Requisição:** {ext.get('numero_requisicao') or 'Não localizada'}")
            st.write(f"**Dotação extraída:** {ext.get('dotacao_texto') or 'Não localizada'}")
            st.write(f"**Emissor da Requisição:** {ext.get('emissor_requisicao') or 'Não localizado'}")
            st.write(f"**Autoridade assinante:** {ext.get('autoridade_assinante') or 'Não localizada'}")
            st.write(f"**Cargo da autoridade:** {ext.get('cargo_autoridade') or 'Não localizado'}")
            st.write(f"**Responsável indicado na Requisição:** {ext.get('responsavel_requisicao') or 'Não localizado'}")

            with st.expander("Valores localizados no processo"):
                for v in ext.get("valores_localizados", []):
                    st.write(f"**{v['arquivo']}**: {', '.join(v['valores'])}")

            st.subheader("Resultado Detalhado dos Documentos Externos")
            for r in resultados:
                arq = f" — arquivo provável: {r['arquivo']}" if r.get("arquivo") else ""
                obs = f"\n\n{r.get('observacao')}" if r.get("observacao") else ""
                if r["status"] == "Encontrado":
                    st.success("✓ " + r["item"] + arq + obs)
                elif r["criticidade"] == "critico":
                    st.error("❌ " + r["item"] + obs)
                else:
                    st.warning("⚠️ " + r["item"] + obs)

            if st.button("Gerar Relatório Técnico de Conferência"):
                pasta = PROC / "Relatorios"
                arquivo = gerar_relatorio_pendencias(resultados, res, ext, classificacao, pasta)
                with open(arquivo, "rb") as f:
                    st.download_button("Baixar Relatório Técnico", f, file_name=arquivo.name)

    with aba2:
        st.header("Dados Complementares")
        ext = st.session_state.get("extraidos", {})
        st.info("Estes dados podem ser preenchidos antes da geração final. Se não forem informados, os documentos indicarão que devem ser complementados na instrução processual.")

        artista = st.text_input("Artista", value=ext.get("artista", ""))
        evento = st.text_input("Evento", value=ext.get("evento", ""))

        descricao_default = ext.get("objeto_sugerido") or (f"Contratação de serviços artísticos para apresentação ao vivo de {artista}" if artista else "")
        descricao = st.text_area("Objeto / necessidade", value=descricao_default, height=100)

        col1, col2 = st.columns(2)
        with col1:
            secretaria = st.text_input("Secretaria", value=secretaria_default(ext))
            data_evento = st.text_input("Data do evento", value=data_evento_default(ext))
            horario = st.text_input("Horário")
            local_evento = st.text_input("Local")
            numero_processo = st.text_input("Número do processo", value=ext.get("processo_numero", ""))
        with col2:
            valor_estimado = st.text_input("Valor estimado", value=ext.get("valor_principal", ""))
            origem_valor = st.text_input("Origem do valor", value=ext.get("origem_valor", ""))
            publico_estimado = st.text_input("Público estimado")
            gestor = st.text_input("Gestor", value="Carla Andressa Dourado", disabled=True)
            fiscal = st.text_input("Fiscal", value="Talita Cristiane Carvalho", disabled=True)

        fonte_recurso = st.text_input("Dotação / fonte de recurso", value=ext.get("dotacao_texto", ""))
        st.subheader("Dados administrativos dos modelos oficiais")
        col3, col4 = st.columns(2)
        with col3:
            numero_dfd = st.text_input("Número do DFD", value="", help="O número do DFD pode ser diferente do processo. Preencha se já houver número oficial; caso contrário, o documento sairá como minuta.")
            numero_requisicao = st.text_input("Número da Requisição", value=ext.get("numero_requisicao", ""))
            data_documento = st.text_input("Data dos documentos", value=data_documento_default(ext))
            data_conclusao = st.text_input("Data pretendida para conclusão", value=data_conclusao_default(ext), help="Normalmente coincide com a data limite antes/até a realização do evento. Deixe em branco se ainda não estiver definida.")
            secretario = st.text_input("Autoridade que assina/aprova", value=ext.get("autoridade_assinante", ""))
            cargo_secretario = st.text_input("Cargo da autoridade", value=ext.get("cargo_autoridade", ""))
        with col4:
            agente_default = ext.get("emissor_requisicao", "")
            agente = st.text_input("Agente responsável ETP/TR", value=agente_default, help="Regra: usar sempre o emissor/agente da Requisição ao Compras.")
            agente_matricula_default = "22019" if "CRISTIAN MARCELO SCHIBELSKY" in agente_default.upper() else ""
            agente_cargo_default = "Diretor de Sub Divisão" if "CRISTIAN MARCELO SCHIBELSKY" in agente_default.upper() else ""
            agente_matricula = st.text_input("Matrícula do agente", value=agente_matricula_default)
            agente_cargo = st.text_input("Cargo do agente", value=agente_cargo_default)
            responsavel_demanda = st.text_input("Responsável pela demanda", value=ext.get("autoridade_assinante", ""))
            matricula = st.text_input("Matrícula do responsável", value="")
            email = st.text_input("Email", value="smct@sumare.sp.gov.br")
            telefone = st.text_input("Telefone", value="(19) 3873–9469")
        observacoes = st.text_area("Observações complementares")

        modelo_tr = st.selectbox(
            "Modelo oficial de TR",
            [
                "Contratação direta - serviços comuns",
                "Contratação direta - aquisição",
                "Pregão - aquisição",
                "Serviços comuns sem dedicação exclusiva",
                "Serviços comuns com dedicação exclusiva",
                "Obras e serviços de engenharia",
                "Modelo TR Lei 14.133 simplificado",
                "Aquisição - licitação ou contratação direta",
                "Ata de Registro de Preços",
            ],
            index=0,
            help="Para contratação artística, usar: Contratação direta - serviços comuns."
        )

        if st.button("Salvar Dados Complementares", type="primary"):
            dados = {
                "descricao": descricao,
                "secretaria": secretaria,
                "data_evento": data_evento,
                "horario": horario,
                "local_evento": local_evento,
                "numero_processo": numero_processo,
                "valor_estimado": valor_estimado,
                "origem_valor": origem_valor,
                "publico_estimado": publico_estimado,
                "gestor": "Carla Andressa Dourado",
                "gestor_cargo": "Diretora de Divisão",
                "gestor_competencias": "Vasto conhecimento sobre as rotinas dos eventos da Secretaria Municipal de Cultura",
                "fiscal": "Talita Cristiane Carvalho",
                "fiscal_cargo": "Diretora de Área",
                "fiscal_competencias": "Vasto conhecimento sobre as rotinas dos eventos da Secretaria Municipal de Cultura",
                "fonte_recurso": fonte_recurso,
                "observacoes": observacoes,
                "modelo_tr": modelo_tr,
                "numero_dfd": numero_dfd,
                "numero_requisicao": numero_requisicao,
                "data_documento": data_documento,
                "data_conclusao": data_conclusao,
                "secretario": secretario,
                "cargo_secretario": cargo_secretario,
                "agente": agente,
                "agente_matricula": agente_matricula,
                "agente_cargo": agente_cargo,
                "responsavel_demanda": responsavel_demanda,
                "matricula": matricula,
                "email": email,
                "telefone": telefone,
            }
            st.session_state["dados_complementares"] = dados
            st.success("Dados complementares salvos para geração dos documentos.")

    with aba3:
        st.header("Geração Técnica de Documentos")
        ext = st.session_state.get("extraidos", {})
        classificacao = st.session_state.get("classificacao") or classificar_demanda("Contratação de show artístico", "Secretaria Municipal de Cultura e Turismo", "")
        dados = st.session_state.get("dados_complementares")

        if not dados:
            artista = ext.get("artista", "")
            dados = {
                "descricao": ext.get("objeto_sugerido") or (f"Contratação de serviços artísticos para apresentação ao vivo de {artista}" if artista else ""),
                "secretaria": "Secretaria Municipal de Cultura e Turismo",
                "data_evento": data_evento_default(ext),
                "horario": "",
                "local_evento": "",
                "numero_processo": ext.get("processo_numero", ""),
                "valor_estimado": ext.get("valor_principal", ""),
                "origem_valor": ext.get("origem_valor", ""),
                "publico_estimado": "",
                "gestor": "Carla Andressa Dourado",
                "gestor_cargo": "Diretora de Divisão",
                "gestor_competencias": "Vasto conhecimento sobre as rotinas dos eventos da Secretaria Municipal de Cultura",
                "fiscal": "Talita Cristiane Carvalho",
                "fiscal_cargo": "Diretora de Área",
                "fiscal_competencias": "Vasto conhecimento sobre as rotinas dos eventos da Secretaria Municipal de Cultura",
                "fonte_recurso": ext.get("dotacao_texto", ""),
                "observacoes": "",
                "modelo_tr": "Contratação direta - serviços comuns",
                "numero_dfd": "",
                "numero_requisicao": ext.get("numero_requisicao", ""),
                "data_documento": data_documento_default(ext),
                "data_conclusao": data_conclusao_default(ext),
                "secretario": ext.get("autoridade_assinante", ""),
                "cargo_secretario": ext.get("cargo_autoridade", ""),
                "agente": ext.get("emissor_requisicao", ""),
                "agente_matricula": "22019" if "CRISTIAN MARCELO SCHIBELSKY" in (ext.get("emissor_requisicao", "") or "").upper() else "",
                "agente_cargo": "Diretor de Sub Divisão" if "CRISTIAN MARCELO SCHIBELSKY" in (ext.get("emissor_requisicao", "") or "").upper() else "",
                "responsavel_demanda": ext.get("autoridade_assinante", ""),
                "matricula": "",
                "email": "smct@sumare.sp.gov.br",
                "telefone": "(19) 3873–9469",
            }

        # Base de Conhecimento Homologada: usa apenas como referência técnica controlada.
        caso_ref = buscar_caso_similar(dados, classificacao)
        if caso_ref:
            st.info("Base homologada localizada para apoio técnico: " + resumo_caso(caso_ref))
            ext["caso_referencia_homologada"] = caso_ref
        else:
            ext["caso_referencia_homologada"] = None

        # Validação final ajustada.
        # Não bloquear por número do DFD: ele pode ser próprio da tramitação e diferir do processo.
        # Não bloquear por gestor/fiscal: estes campos são padronizados pelo projeto.
        campos_criticos_final = [
            ("Número do processo", "numero_processo"),
            ("Data do evento", "data_evento"),
            ("Local do evento", "local_evento"),
            ("Dotação/Fonte", "fonte_recurso"),
        ]

        pendencias_final = []
        for rotulo, chave in campos_criticos_final:
            if not dados.get(chave):
                pendencias_final.append(rotulo)

        # Matrícula do agente só é pendência se houver agente identificado, mas a matrícula não foi preenchida.
        if dados.get("agente") and not dados.get("agente_matricula"):
            pendencias_final.append("Matrícula do agente")

        if pendencias_final:
            st.info("Modo minuta: ainda faltam campos administrativos para versão final: " + ", ".join(pendencias_final))
        else:
            st.success("Dados administrativos essenciais preenchidos para geração final. Revise o conteúdo antes do uso oficial.")

        st.warning("Os documentos abaixo serão gerados com redação técnico-jurídica e deverão ser revisados antes de uso oficial.")

        a, b, c, d, e = st.columns(5)
        with a:
            if st.button("Gerar Requisição"):
                baixar_doc("Requisição", gerar_requisicao, dados, classificacao, ext)
        with b:
            if st.button("Gerar DFD"):
                baixar_doc("DFD", gerar_dfd, dados, classificacao, ext)
        with c:
            if st.button("Gerar ETP"):
                baixar_doc("ETP", gerar_etp, dados, classificacao, ext)
        with d:
            if st.button("Gerar TR"):
                baixar_doc("TR", gerar_tr, dados, classificacao, ext)
        with e:
            if st.button("Gerar Processo Completo"):
                baixar_processo_completo(dados, classificacao, ext)

        st.divider()
        st.subheader("Base de Conhecimento Homologada")

        st.caption(
            "Use este recurso somente depois de revisar o processo. "
            "A base homologada não aprende automaticamente com erro: ela guarda apenas processos aprovados por você como referência."
        )

        col_h1, col_h2 = st.columns(2)
        with col_h1:
            obs_homologacao = st.text_area(
                "Observações da homologação",
                value="Processo revisado e aceito como referência técnica para contratações artísticas por inexigibilidade.",
                height=80,
            )
            if st.button("Adicionar último processo à Base Homologada"):
                pasta_ultimo = st.session_state.get("ultimo_processo_pasta")
                dados_ultimo = st.session_state.get("ultimo_processo_dados")
                class_ultimo = st.session_state.get("ultimo_processo_classificacao")
                if not pasta_ultimo or not dados_ultimo:
                    st.warning("Gere o Processo Completo antes de adicionar à Base Homologada.")
                else:
                    caso = adicionar_processo_homologado(pasta_ultimo, dados_ultimo, class_ultimo, obs_homologacao)
                    st.success(f"Processo adicionado à Base Homologada: {caso.get('id')}")

        with col_h2:
            casos = carregar_casos()
            st.metric("Casos homologados", len(casos))
            if casos:
                ultimo = casos[-1]
                st.write("Último caso homologado:")
                st.code(resumo_caso(ultimo))

    st.divider()
    st.subheader("Revisor IA via API")

    st.caption(
        "A IA revisa, aponta, sugere e gera versões aprimoradas para revisão pessoal. "
        "Ela não homologa automaticamente: a decisão final continua sendo sua."
    )

    with st.expander("Configuração da API de Revisão IA"):
        api_key = st.text_input(
            "API Key",
            value="",
            type="password",
            help="A chave não é gravada pelo sistema. Ela é usada apenas nesta sessão."
        )
        endpoint = st.text_input(
            "Endpoint compatível com Chat Completions",
            value="https://api.openai.com/v1/chat/completions",
        )
        modelo = st.text_input(
            "Modelo",
            value="gpt-4o-mini",
            help="Pode ser alterado conforme o provedor/API utilizada."
        )
        mascarar = st.checkbox(
            "Mascarar CPF/CNPJ/RG antes de enviar para a API",
            value=True,
        )

    col_ia1, col_ia2 = st.columns(2)

    with col_ia1:
        if st.button("Revisar e Melhorar último processo com IA"):
            pasta_ultimo = st.session_state.get("ultimo_processo_pasta")
            dados_ultimo = st.session_state.get("ultimo_processo_dados")
            if not pasta_ultimo or not dados_ultimo:
                st.warning("Gere o Processo Completo antes de revisar com IA.")
            elif not api_key:
                st.warning("Informe a API Key para executar a revisão IA.")
            else:
                with st.spinner("Revisor IA analisando DFD, ETP e TR..."):
                    resultado = revisar_processo_com_ia(
                        pasta_ultimo,
                        dados_ultimo,
                        api_key=api_key,
                        endpoint=endpoint,
                        model=modelo,
                        mascarar=mascarar,
                    )
                if not resultado.get("ok"):
                    st.error(resultado.get("erro") or "Falha na revisão IA.")
                else:
                    st.success("Revisão IA concluída. Foram gerados relatório e versões revisadas.")
                    st.session_state["ultima_revisao_ia_pasta"] = str(resultado.get("pasta_revisao"))
                    st.session_state["ultimo_processo_pasta_revisado"] = pasta_ultimo
                    st.markdown("### Resumo retornado pela IA")
                    st.write(resultado.get("relatorio", "")[:5000])

                    zip_rev = zipar_pasta(Path(resultado.get("pasta_revisao")), "REVISAO_IA.zip")
                    with open(zip_rev, "rb") as f:
                        st.download_button("Baixar Revisão IA (.zip)", f, file_name=zip_rev.name)

    with col_ia2:
        st.info(
            "Fluxo recomendado: 1) Gere o processo completo; 2) Rode a revisão IA; "
            "3) Leia o relatório e os DOCX revisados; 4) Ajuste manualmente se necessário; "
            "5) Homologue somente a versão aprovada."
        )
        if st.button("Homologar versão revisada pela IA"):
            pasta_ultimo = st.session_state.get("ultimo_processo_pasta_revisado") or st.session_state.get("ultimo_processo_pasta")
            pasta_revisao = st.session_state.get("ultima_revisao_ia_pasta")
            dados_ultimo = st.session_state.get("ultimo_processo_dados")
            class_ultimo = st.session_state.get("ultimo_processo_classificacao")
            if not pasta_ultimo or not dados_ultimo:
                st.warning("Não há processo gerado/revisado para homologar.")
            elif not pasta_revisao:
                st.warning("Execute primeiro a revisão IA e confira os arquivos revisados.")
            else:
                obs = "Processo revisado com apoio de IA e aprovado manualmente para compor a Base Homologada."
                caso = adicionar_processo_homologado(pasta_ultimo, dados_ultimo, class_ultimo, obs)
                st.success(f"Versão revisada homologada na Base de Conhecimento: {caso.get('id')}")

    st.divider()
    st.caption("Build 1.0 RC29 — Base Homologada + Revisor IA via API. A IA revisa e sugere; a homologação continua manual.")

if __name__ == "__main__":
    main()