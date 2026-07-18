# 🐇 Lebre de Junho - API (Backend)

Este repositório contém a API REST que serve como o core do ecossistema **Lebre de Junho**. Desenvolvida em **Laravel 11**, ela é responsável pela regra de negócios, persistência de dados, autenticação de usuários e integração com serviços externos.

---

## Ecossistema Frontend

Esta API foi desenhada para alimentar uma interface reativa e moderna baseada em Nuxt 3.

**[Acessar Repositório do Frontend](https://github.com/saymonp/lebre-de-junho-frontend)**

---

## Funcionalidades Principais

*   **Autenticação Segura:** Controle de acesso e proteção de rotas administrativas via API Tokens (Laravel Sanctum).
*   **Integração TMDB:** Consumo da API do The Movie Database para busca, criação e atualização de filmes e coleções diretamente no banco de dados local.
*   **Transações de Banco de Dados:** Operações complexas encapsuladas de forma segura em `DB::transaction` para garantir a integridade dos dados de mídia e usuários.
*   **Arquitetura RESTful:** Endpoints padronizados retornando respostas estruturadas em JSON.

---

## Tecnologias Utilizadas

*   **PHP**
*   **Laravel** - Framework PHP robusto e elegante
*   **Laravel Sanctum** - Sistema leve de autenticação para SPA e APIs
*   **MySQL / PostgreSQL** - Banco de dados relacional para persistência de dados
*   **Docker** – Containerização do ambiente de desenvolvimento (Docker Compose) para garantir paridade entre os ambientes
*   **FrankenPHP** – Servidor de aplicação PHP moderno, construído sobre o Caddy, utilizado no ambiente de produção para entregar máxima performance com suporte nativo a HTTP/3, Early Hints e Worker Mode.

---

## Instalação e Execução Local

### 1. Clonar o repositório e instalar as dependências
```bash
git clone [https://github.com/saymonp/lebre-de-junho-api.git](https://github.com/saymonp/lebre-de-junho-api.git)
cd lebre-de-junho-api
composer install
php artisan migrate
php artisan serve