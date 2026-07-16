# MTD STORE

Este é um monorepo que contém o e-commerce MTD STORE, desenvolvido com uma arquitetura híbrida e desacoplada.

## Estrutura

- `backend/`: API em Laravel (Motor do sistema) e Painel Administrativo (Filament).
- `frontend/`: Vitrine em Next.js (App Router).

## Requisitos

- Docker e Docker Compose

## Como iniciar (Ambiente Local)

1. Clone o repositório.
2. Copie os arquivos `.env.example` para `.env` tanto na pasta `backend/` quanto `frontend/`.
3. Na raiz do projeto, execute:
   ```bash
   docker-compose up -d
   ```
4. O backend (API + Filament) estará disponível em `http://localhost:8000` (Painel admin em `http://localhost:8000/admin`).
5. O frontend (Next.js) estará disponível em `http://localhost:3000`.

## Testes e CI/CD

O projeto utiliza GitHub Actions para validação de testes e linting de ambos os ecossistemas. A cobertura de código na camada de Services e Gateways do backend é monitorada ativamente.
