# Projeto Modulos — Pequenos Negocios

## Objetivo

Criar uma matriz comercial modular para pequenos negocios, permitindo que cada cliente contrate apenas as capacidades de que realmente precisa. A mesma matriz deve ser reutilizavel pela Vitrine IA Factory em produtos, verticais e implantacoes white label.

## Principio arquitetural

Os modulos nao sao sistemas isolados. Eles compartilham o Nucleo Base e contratos de integracao padronizados. Entidades centrais devem ser reutilizadas e nao duplicadas entre modulos.

## Nucleo Base

Responsavel por tenant, usuarios, permissoes, branding, configuracoes, dashboard e ativacao/licenciamento de modulos.

## Primeira familia: Gestao do Negocio

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

O Modulo Cadastro e o primeiro modulo em desenvolvimento e deve servir como referencia para os demais.

## Segunda familia: Presenca Digital e Conteudo

A Factory ja possui produtos e capacidades relacionadas a TV Digital, Guia Digital e portais. Esta familia devera ser mapeada posteriormente para componentes reutilizaveis, evitando reconstruir funcionalidades ja existentes.

Capacidades candidatas: noticias, videos, guia comercial, classificados, empregos, eventos, galeria, artigos, PWA, push, newsletter, flipbook, streaming e podcasts.

## Regra de reutilizacao

Antes de criar uma nova funcionalidade:

1. Verificar modulo existente.
2. Verificar capacidade existente em produtos atuais.
3. Reutilizar entidade, evento ou componente quando possivel.
4. Criar somente a lacuna necessaria.
5. Registrar a nova capacidade no catalogo modular.

## Modelo comercial

O cliente pode iniciar com poucos modulos e ativar novos conforme sua necessidade. Exemplo:

- Salao: Cadastro + Agenda + WhatsApp.
- Oficina: Cadastro + Orcamentos + Ordem de Servico + Estoque + Financeiro.
- Escola: Cadastro + Cursos/Turmas + Financeiro + WhatsApp.
- Associacao: Cadastro + Atendimento + Documentos + Comunicacao.

## Relacao com a Factory

A matriz modular e a fonte reutilizavel. A Factory deve compor produtos e solucoes a partir desses modulos, aplicando configuracao, branding, regras do cliente, homologacao e deploy.
