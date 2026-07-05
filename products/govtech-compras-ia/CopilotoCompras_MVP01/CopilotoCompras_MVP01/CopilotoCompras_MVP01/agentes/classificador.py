from dataclasses import dataclass, asdict
from typing import List, Dict


@dataclass
class Classificacao:
    objeto_resumido: str
    categoria: str
    modalidade: str
    secretaria: str
    produto_sugerido: str
    fundamentacao: str
    documentos_necessarios: List[str]
    pendencias: List[str]


def classificar_demanda(descricao: str, secretaria_informada: str = "", valor_estimado: str = "") -> Dict:
    texto = descricao.lower()

    # Regras iniciais locais para o MVP 0.1
    if any(palavra in texto for palavra in ["show", "cantor", "cantora", "artista", "apresentação artística", "gospel"]):
        classificacao = Classificacao(
            objeto_resumido="Contratação de apresentação artística",
            categoria="Serviço artístico / Evento",
            modalidade="Inexigibilidade",
            secretaria=secretaria_informada or "Secretaria Municipal de Cultura e Turismo",
            produto_sugerido="Serviços artísticos / apresentação ao vivo",
            fundamentacao="Art. 74 da Lei Federal nº 14.133/2021, conforme análise jurídica posterior.",
            documentos_necessarios=[
                "Proposta comercial",
                "Carta/contrato de exclusividade",
                "Comprovação de consagração pública ou crítica especializada",
                "Justificativa de preço",
                "CNPJ/contrato social",
                "Certidões de regularidade",
            ],
            pendencias=[]
        )
    elif any(palavra in texto for palavra in ["notebook", "computador", "tablet", "impressora"]):
        classificacao = Classificacao(
            objeto_resumido="Aquisição de equipamentos de informática",
            categoria="Aquisição de bens",
            modalidade="Pregão Eletrônico ou Dispensa, conforme valor e planejamento",
            secretaria=secretaria_informada or "Secretaria requisitante",
            produto_sugerido="Equipamento de informática",
            fundamentacao="Lei Federal nº 14.133/2021, conforme enquadramento posterior.",
            documentos_necessarios=[
                "DFD",
                "ETP ou justificativa de dispensa",
                "Termo de Referência",
                "Pesquisa de preços",
                "Disponibilidade orçamentária",
            ],
            pendencias=[]
        )
    else:
        classificacao = Classificacao(
            objeto_resumido="Objeto a classificar",
            categoria="Não classificado automaticamente",
            modalidade="Pendente de análise",
            secretaria=secretaria_informada or "Secretaria requisitante",
            produto_sugerido="Pendente",
            fundamentacao="Pendente de análise",
            documentos_necessarios=[
                "DFD",
                "Termo de Referência",
                "Pesquisa de preços",
                "Disponibilidade orçamentária",
            ],
            pendencias=["Classificação manual recomendada"]
        )

    if not valor_estimado:
        classificacao.pendencias.append("Valor estimado não informado")

    if not any(p in texto for p in ["data", "dia", "dezembro", "janeiro", "fevereiro", "março", "abril", "maio", "junho", "julho", "agosto", "setembro", "outubro", "novembro"]):
        classificacao.pendencias.append("Data ou prazo de execução não informado")

    return asdict(classificacao)