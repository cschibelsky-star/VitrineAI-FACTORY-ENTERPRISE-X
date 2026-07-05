import sys
import sqlite3
from pathlib import Path
from datetime import datetime

import streamlit as st

# Permite importar módulos fora da pasta app
ROOT = Path(__file__).resolve().parents[1]
sys.path.append(str(ROOT))

from agentes.classificador import classificar_demanda
from agentes.dfd import gerar_dfd


DB_PATH = ROOT / "banco" / "compras.db"
PROCESSOS_DIR = ROOT / "processos"


def init_db():
    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()
    cur.execute("""
    CREATE TABLE IF NOT EXISTS processos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        objeto TEXT NOT NULL,
        secretaria TEXT,
        modalidade TEXT,
        categoria TEXT,
        valor_estimado TEXT,
        status TEXT DEFAULT 'RASCUNHO',
        data_criacao TEXT NOT NULL
    )
    """)
    cur.execute("""
    CREATE TABLE IF NOT EXISTS documentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        processo_id INTEGER NOT NULL,
        tipo TEXT NOT NULL,
        arquivo TEXT NOT NULL,
        data_criacao TEXT NOT NULL,
        FOREIGN KEY(processo_id) REFERENCES processos(id)
    )
    """)
    conn.commit()
    conn.close()


def salvar_processo(descricao, classificacao, valor_estimado):
    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()
    cur.execute(
        """
        INSERT INTO processos 
        (objeto, secretaria, modalidade, categoria, valor_estimado, status, data_criacao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        """,
        (
            descricao,
            classificacao.get("secretaria"),
            classificacao.get("modalidade"),
            classificacao.get("categoria"),
            valor_estimado,
            "RASCUNHO",
            datetime.now().isoformat()
        )
    )
    processo_id = cur.lastrowid
    conn.commit()
    conn.close()
    return processo_id


def salvar_documento(processo_id, tipo, arquivo):
    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()
    cur.execute(
        """
        INSERT INTO documentos
        (processo_id, tipo, arquivo, data_criacao)
        VALUES (?, ?, ?, ?)
        """,
        (processo_id, tipo, str(arquivo), datetime.now().isoformat())
    )
    conn.commit()
    conn.close()


def main():
    st.set_page_config(page_title="Copiloto de Compras IA", layout="wide")
    init_db()

    st.title("Copiloto de Compras Públicas IA — MVP 0.1")
    st.caption("Versão local para notebook | Demanda → Classificação → DFD")

    st.header("Nova Contratação")

    descricao = st.text_area(
        "Descreva a necessidade",
        placeholder="Ex.: Contratação de show da cantora Gabriela Rocha para o Natal para Jesus.",
        height=130
    )

    col1, col2 = st.columns(2)
    with col1:
        secretaria = st.selectbox(
            "Secretaria",
            [
                "Secretaria Municipal de Cultura e Turismo",
                "Secretaria Municipal de Educação",
                "Secretaria Municipal de Saúde",
                "Secretaria Municipal de Administração",
                "Secretaria requisitante"
            ]
        )

    with col2:
        valor_estimado = st.text_input("Valor estimado", placeholder="Ex.: R$ 300.000,00")

    if st.button("Analisar demanda", type="primary"):
        if not descricao.strip():
            st.error("Informe a descrição da necessidade.")
            return

        classificacao = classificar_demanda(descricao, secretaria, valor_estimado)
        st.session_state["descricao"] = descricao
        st.session_state["secretaria"] = secretaria
        st.session_state["valor_estimado"] = valor_estimado
        st.session_state["classificacao"] = classificacao

    if "classificacao" in st.session_state:
        classificacao = st.session_state["classificacao"]

        st.subheader("Resultado da Classificação")

        c1, c2, c3 = st.columns(3)
        c1.metric("Modalidade sugerida", classificacao.get("modalidade"))
        c2.metric("Categoria", classificacao.get("categoria"))
        c3.metric("Secretaria", classificacao.get("secretaria"))

        st.write("**Objeto resumido:**", classificacao.get("objeto_resumido"))
        st.write("**Produto sugerido:**", classificacao.get("produto_sugerido"))
        st.write("**Fundamentação inicial:**", classificacao.get("fundamentacao"))

        st.write("### Documentos necessários")
        for doc in classificacao.get("documentos_necessarios", []):
            st.write(f"✓ {doc}")

        st.write("### Pendências")
        pendencias = classificacao.get("pendencias", [])
        if pendencias:
            for p in pendencias:
                st.warning(p)
        else:
            st.success("Nenhuma pendência inicial identificada.")

        if st.button("Gerar DFD inicial"):
            processo_id = salvar_processo(
                st.session_state["descricao"],
                classificacao,
                st.session_state["valor_estimado"]
            )
            pasta_processo = PROCESSOS_DIR / f"Processo_{processo_id:04d}"
            arquivo = gerar_dfd(
                processo_id,
                st.session_state["descricao"],
                classificacao,
                st.session_state["valor_estimado"],
                pasta_processo
            )
            salvar_documento(processo_id, "DFD", arquivo)
            st.success(f"DFD gerado com sucesso: {arquivo.name}")
            with open(arquivo, "rb") as f:
                st.download_button(
                    "Baixar DFD.docx",
                    data=f,
                    file_name=f"DFD_Processo_{processo_id:04d}.docx",
                    mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                )

    st.divider()
    st.caption("MVP 0.1: classificação local por regras. Próxima fase: integração com OpenAI e geração avançada.")


if __name__ == "__main__":
    main()