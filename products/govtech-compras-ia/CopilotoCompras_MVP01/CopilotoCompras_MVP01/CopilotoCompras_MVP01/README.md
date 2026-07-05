# Copiloto de Compras Públicas IA Local — MVP 0.1

Este MVP roda localmente no notebook e faz:

1. Recebe uma demanda de contratação.
2. Classifica a demanda.
3. Gera um checklist básico.
4. Salva o processo em SQLite.
5. Gera um DFD inicial em DOCX.

## Como instalar

1. Instale Python 3.10 ou superior.
2. Abra o terminal dentro da pasta do projeto.
3. Rode:

```bash
pip install -r requirements.txt
```

4. Execute:

```bash
streamlit run app/main.py
```

## Observação

Nesta versão, o classificador usa regras locais simples.
A integração com OpenAI pode ser ativada depois no arquivo `agentes/classificador.py`.