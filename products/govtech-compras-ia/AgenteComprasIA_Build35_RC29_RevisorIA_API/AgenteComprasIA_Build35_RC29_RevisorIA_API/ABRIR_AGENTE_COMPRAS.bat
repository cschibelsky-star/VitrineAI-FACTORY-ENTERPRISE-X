@echo off
title Agente Compras IA - Build 1.0 RC9 Formatacao Layout Profissional
cd /d "%~dp0"
echo Iniciando Agente Compras IA Build 1.0 RC9 - Formatacao Layout Profissional pela Requisicao...
echo.

if not exist "%~dp0app\main.py" (
    echo ERRO: app\main.py nao encontrado.
    pause
    exit /b 1
)

where python >nul 2>nul
if errorlevel 1 (
    echo ERRO: Python nao encontrado.
    echo Instale o Python antes de abrir o sistema.
    pause
    exit /b 1
)

python -m pip install streamlit python-docx python-dotenv openai pypdf docx2pdf pywin32 docx2pdf pywin32 docx2pdf
python -m streamlit run "%~dp0app\main.py"
pause
