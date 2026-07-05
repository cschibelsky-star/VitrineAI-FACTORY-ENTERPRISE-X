import sqlite3
from pathlib import Path

DB_PATH = Path(__file__).parent / "compras.db"

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

if __name__ == "__main__":
    init_db()
    print(f"Banco inicializado em: {DB_PATH}")