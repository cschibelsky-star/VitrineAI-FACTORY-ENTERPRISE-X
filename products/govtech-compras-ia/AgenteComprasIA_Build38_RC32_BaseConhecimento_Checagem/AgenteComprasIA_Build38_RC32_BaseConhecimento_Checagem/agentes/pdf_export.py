from pathlib import Path
import os
import platform
import shutil
import subprocess
import time


PDF_FORMAT_WORD = 17


def _candidate_soffice_paths():
    names = ["soffice", "libreoffice"]
    found = []

    for n in names:
        p = shutil.which(n)
        if p:
            found.append(p)

    if platform.system().lower().startswith("win"):
        common = [
            r"C:\Program Files\LibreOffice\program\soffice.exe",
            r"C:\Program Files (x86)\LibreOffice\program\soffice.exe",
            r"C:\Program Files\LibreOffice 7\program\soffice.exe",
            r"C:\Program Files\LibreOffice 24\program\soffice.exe",
        ]
        found.extend([p for p in common if Path(p).exists()])

        for base in [os.environ.get("PROGRAMFILES"), os.environ.get("PROGRAMFILES(X86)")]:
            if base and Path(base).exists():
                for p in Path(base).glob("LibreOffice*/program/soffice.exe"):
                    if p.exists():
                        found.append(str(p))

    out = []
    seen = set()
    for p in found:
        ps = str(p)
        if ps.lower() not in seen:
            out.append(ps)
            seen.add(ps.lower())
    return out


def _converter_por_libreoffice(docx_path, out_dir):
    erros = []
    for soffice in _candidate_soffice_paths():
        try:
            cmd = [
                str(soffice),
                "--headless",
                "--convert-to",
                "pdf",
                "--outdir",
                str(out_dir),
                str(docx_path),
            ]
            r = subprocess.run(cmd, check=False, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=120)
            pdf = out_dir / f"{docx_path.stem}.pdf"
            if r.returncode == 0 and pdf.exists() and pdf.stat().st_size > 0:
                return pdf, ""
            msg = r.stderr.decode(errors="ignore") or r.stdout.decode(errors="ignore")
            erros.append(f"LibreOffice em {soffice}: {msg}")
        except Exception as e:
            erros.append(f"LibreOffice em {soffice}: {e}")
    return None, "\n".join(erros)


def _converter_por_docx2pdf(docx_path, out_dir):
    # Usa o pacote docx2pdf, que por baixo usa Microsoft Word no Windows.
    if not platform.system().lower().startswith("win"):
        return None, "docx2pdf/Microsoft Word disponível apenas no Windows."
    try:
        from docx2pdf import convert
    except Exception as e:
        return None, f"Pacote docx2pdf não disponível: {e}"

    try:
        pdf = out_dir / f"{docx_path.stem}.pdf"
        convert(str(docx_path.resolve()), str(pdf.resolve()))
        if pdf.exists() and pdf.stat().st_size > 0:
            return pdf, ""
        return None, "docx2pdf executou, mas não retornou PDF válido."
    except Exception as e:
        return None, f"Erro no docx2pdf/Microsoft Word: {e}"


def _converter_por_word_com(docx_path, out_dir):
    # Fallback direto via COM do Microsoft Word.
    if not platform.system().lower().startswith("win"):
        return None, "Automação COM do Word disponível apenas no Windows."

    try:
        import pythoncom
        import win32com.client
    except Exception as e:
        return None, f"pywin32/win32com não disponível: {e}"

    word = None
    doc = None
    try:
        pythoncom.CoInitialize()
        word = win32com.client.DispatchEx("Word.Application")
        word.Visible = False
        word.DisplayAlerts = 0

        docx_abs = str(Path(docx_path).resolve())
        pdf = Path(out_dir) / f"{Path(docx_path).stem}.pdf"
        pdf_abs = str(pdf.resolve())

        doc = word.Documents.Open(docx_abs, ReadOnly=True)
        doc.SaveAs(pdf_abs, FileFormat=PDF_FORMAT_WORD)
        doc.Close(False)
        doc = None
        word.Quit()
        word = None

        for _ in range(10):
            if pdf.exists() and pdf.stat().st_size > 0:
                return pdf, ""
            time.sleep(0.5)

        return None, "Microsoft Word abriu o documento, mas o PDF não foi criado ou ficou vazio."
    except Exception as e:
        return None, f"Erro na automação direta do Microsoft Word/COM: {e}"
    finally:
        try:
            if doc is not None:
                doc.Close(False)
        except Exception:
            pass
        try:
            if word is not None:
                word.Quit()
        except Exception:
            pass
        try:
            pythoncom.CoUninitialize()
        except Exception:
            pass


def converter_docx_para_pdf(docx_path, out_dir=None):
    docx_path = Path(docx_path)
    out_dir = Path(out_dir or docx_path.parent)
    out_dir.mkdir(parents=True, exist_ok=True)

    erros = []

    # 1. LibreOffice
    pdf, err = _converter_por_libreoffice(docx_path, out_dir)
    if pdf:
        return pdf, erros
    if err:
        erros.append(err)

    # 2. docx2pdf / Word
    pdf, err = _converter_por_docx2pdf(docx_path, out_dir)
    if pdf:
        return pdf, erros
    if err:
        erros.append(err)

    # 3. COM direto do Word
    pdf, err = _converter_por_word_com(docx_path, out_dir)
    if pdf:
        return pdf, erros
    if err:
        erros.append(err)

    return None, erros


def criar_conversor_manual(pasta_processo, pasta_docx, pasta_pdf):
    pasta_processo = Path(pasta_processo)
    bat = pasta_processo / "CONVERTER_PARA_PDF.bat"

    conteudo = '''@echo off
title Converter Processo para PDF
cd /d "%~dp0"

echo Tentando converter com Microsoft Word via PowerShell...
powershell -ExecutionPolicy Bypass -NoProfile -Command "$ErrorActionPreference='Stop'; $word=New-Object -ComObject Word.Application; $word.Visible=$false; New-Item -ItemType Directory -Force -Path 'PDF' | Out-Null; Get-ChildItem 'DOCX\\*.docx' | ForEach-Object { $doc=$word.Documents.Open($_.FullName, $false, $true); $pdf=(Join-Path (Resolve-Path 'PDF') ($_.BaseName + '.pdf')); $doc.SaveAs([ref]$pdf, [ref]17); $doc.Close($false) }; $word.Quit()"
if %ERRORLEVEL% EQU 0 (
  echo.
  echo Conversao por Microsoft Word finalizada. Verifique a pasta PDF.
  pause
  exit /b 0
)

echo.
echo Microsoft Word nao conseguiu converter. Tentando LibreOffice...

set SOFFICE=
if exist "C:\\Program Files\\LibreOffice\\program\\soffice.exe" set "SOFFICE=C:\\Program Files\\LibreOffice\\program\\soffice.exe"
if exist "C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe" set "SOFFICE=C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe"

if "%SOFFICE%"=="" (
  echo LibreOffice nao encontrado.
  echo.
  echo Instale o LibreOffice ou confirme se o Microsoft Word esta abrindo normalmente.
  echo.
  pause
  exit /b 1
)

if not exist "PDF" mkdir "PDF"

echo Convertendo documentos DOCX para PDF com LibreOffice...
for %%f in ("DOCX\\*.docx") do (
  "%SOFFICE%" --headless --convert-to pdf --outdir "PDF" "%%f"
)

echo.
echo Conversao finalizada. Verifique a pasta PDF.
pause
'''
    bat.write_text(conteudo, encoding="utf-8")
    return bat


def gerar_pdfs_documentos(arquivos_docx, out_dir, pasta_processo=None, pasta_docx=None):
    pdfs = []
    falhas = []
    erros_por_arquivo = {}
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)

    for docx in arquivos_docx:
        pdf, erros = converter_docx_para_pdf(docx, out_dir)
        if pdf:
            pdfs.append(pdf)
        else:
            nome = Path(docx).name
            falhas.append(nome)
            erros_por_arquivo[nome] = erros

    bat = None
    if falhas and pasta_processo and pasta_docx:
        bat = criar_conversor_manual(pasta_processo, pasta_docx, out_dir)

    return pdfs, falhas, erros_por_arquivo, bat