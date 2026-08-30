# Projeto Modulos — Pequenos Negocios

## Objetivo

Criar uma matriz comercial modular exclusiva para pequenos negocios, permitindo que cada cliente contrate apenas as capacidades de que realmente precisa.

Esta matriz e independente dos projetos ja existentes da Vitrine IA Pro. Os projetos atuais permanecem fora deste catalogo comercial e ficam em OFF nesta frente. Eles continuam disponiveis como ativos separados para reutilizacao pela Vitrine IA Factory quando necessario.

## Principio arquitetural

Os modulos desta matriz nao sao sistemas isolados. Eles compartilham o Nucleo Base e contratos de integracao padronizados. Entidades centrais devem ser reutilizadas e nao duplicadas entre modulos.

## Nucleo Base

Responsavel por tenant, usuarios, permissoes, branding, configuracoes, dashboard e ativacao/licenciamento de modulos.

## Familia ativa: Gestao do Negocio

- Cadastro
- Agenda
- Atendimento
- CRM
- Orcamentos
- Financeiro
- Estoque
- Ordem de Servico
- Documentos
- WhatsApp
- Marketing

O Modulo Cadastro e o primeiro modulo em desenvolvimento e deve servir como referencia arquitetural e funcional para os demais.

## Projetos existentes — OFF nesta matriz

Projetos ja existentes nao devem ser incorporados ao catalogo comercial de pequenos negocios. Eles permanecem separados e reutilizaveis pela Factory como ativos independentes.

Exemplos:

- TV Digital Enterprise
- Guia Digital
- Assessor Gov
- Compras IA
- Cursos IA
- VIA
- demais produtos e verticais ja existentes

A Factory pode combinar esses ativos com a matriz de pequenos negocios quando houver uma demanda futura, mas essa composicao acontece no nivel da Factory e nao altera o catalogo comercial desta matriz.

## Regra de reutilizacao

Antes de criar uma nova funcionalidade dentro da matriz de pequenos negocios:

1. Verificar se ja existe um modulo nesta matriz.
2. Reutilizar entidades, eventos e componentes comuns do Nucleo Base.
3. Criar somente a lacuna necessaria.
4. Registrar a nova capacidade no catalogo modular.
5. Nao incorporar automaticamente projetos existentes ao catalogo comercial.

## Modelo comercial

O cliente pode iniciar com poucos modulos e ativar novos conforme sua necessidade.

Exemplos:

- Salao: Cadastro + Agenda + WhatsApp.
- Oficina: Cadastro + Orcamentos + Ordem de Servico + Estoque + Financeiro.
- Escola pequena: Cadastro + Agenda + Financeiro + WhatsApp.
- Associacao: Cadastro + Atendimento + Documentos + WhatsApp.

## Relacao com a Factory

A matriz de pequenos negocios e uma fonte comercial modular propria.

A Factory pode reutilizar:

1. os modulos desta matriz; e
2. os projetos existentes mantidos separadamente.

A composicao final de produtos, verticais, white labels e solucoes sob demanda e responsabilidade da Factory.
