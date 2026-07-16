FROM node:20-alpine

# Define o diretório de trabalho
WORKDIR /app

# Em dev, não vamos copiar o código nem instalar as dependências aqui,
# pois usaremos volumes e o comando next dev através do docker-compose.
# Este Dockerfile pode ser estendido para produção (multi-stage build) no futuro.

# Expõe a porta padrão do Next.js
EXPOSE 3000

# Comando padrão (será sobrescrito pelo docker-compose em dev)
CMD ["npm", "run", "dev"]
