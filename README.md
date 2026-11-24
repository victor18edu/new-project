<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🐳 Ambiente com Docker Compose

### 1. Subir o ambiente
```bash
cd back/backend
cp .env.example .env
docker-compose up -d --build
```

Serviços:
- `laravel_app` — PHP 8.3 FPM (porta 9000 interna)
- `laravel_nginx` — Nginx (http://localhost:8000)
- `laravel_db` — MySQL 8.0 (porta 3306)

### 2. Banco de dados
```bash
# Verificar status das migrations
docker exec laravel_app php artisan migrate:status

# Rodar migrations
docker exec laravel_app php artisan migrate

# Rollback (se necessário)
docker exec laravel_app php artisan migrate:rollback
```

### 3. Swagger / OpenAPI
```bash
# Gerar documentação
docker exec laravel_app php artisan l5-swagger:generate

# Acessar UI
http://localhost:8000/api/documentation
```

### 4. Comandos úteis
| Comando | Descrição |
|---------|-----------|
| `docker-compose ps` | Status dos containers |
| `docker-compose down` | Parar e remover containers |
| `docker exec laravel_app composer install` | Instalar dependências PHP |
| `docker exec laravel_app php artisan key:generate` | Gerar APP_KEY |
```

## 💬 WhatsApp (Evolution API + QR Code)

O CRM usa a **Evolution API** conectada por QR Code, com **uma única instância/número** para todos
os usuários. O histórico fica vinculado ao contato (`whatsapp_messages`) e aparece na inbox, na tela
do contato e na API.

### ⚠️ Aviso de risco

O pareamento por QR usa o protocolo do WhatsApp Web, que é **não oficial**. Mesmo com os cuidados
abaixo existe **risco residual de bloqueio do número**:

- use um número **dedicado**, nunca o número principal da empresa;
- mantenha uso humano normal (não deixe o número parecer apenas um bot);
- não faça disparos em massa e evite padrões repetitivos;
- não envie para números que nunca tiveram contato;
- evite recriar a instância ou gerar novos QR Codes sem necessidade — a sessão deve ficar estável.

### 1. Subir a Evolution API

O `docker-compose.yml` já inclui `evolution_api`, `evolution_db` (Postgres) e `evolution_redis`,
todos com volumes persistentes:

```bash
docker compose up -d evolution_api evolution_db evolution_redis
docker compose ps
```

A API fica disponível internamente em `http://evolution_api:8080` e, neste ambiente, também em
`http://localhost:8080`.

### 2. Configuração no Laravel

Preencha no `.env` (valores secretos não devem ser commitados):

```env
EVOLUTION_API_URL=http://evolution_api:8080
EVOLUTION_API_KEY=<chave-longa-e-aleatoria>
EVOLUTION_DB_PASSWORD=<senha-longa-e-aleatoria>
EVOLUTION_SERVER_URL=http://localhost:8080
EVOLUTION_INSTANCE=crm
EVOLUTION_WEBHOOK_TOKEN=<outro-token-longo-e-aleatorio>
EVOLUTION_WEBHOOK_URL=http://host.docker.internal:8000/api/v1/whatsapp/webhook
```

Em produção, troque `EVOLUTION_WEBHOOK_URL` pela URL HTTPS pública do CRM. Depois:

```bash
docker exec laravel_app php artisan config:clear
docker exec laravel_app php artisan migrate
docker exec laravel_app php artisan queue:restart
```

Sem o worker rodando, as mensagens ficam com status **Na fila**:

```bash
docker exec laravel_app php artisan queue:work --tries=1
```

### 3. Parear o número (somente admin)

1. Acesse o painel com um usuário `admin`.
2. No menu lateral, abra **Conexão WhatsApp** (ou acesse `/whatsapp/conexao`).
3. Clique em **Criar / configurar instância**. Isso cria a instância única e registra o webhook de
   `MESSAGES_UPSERT` e `MESSAGES_UPDATE` com o header `X-Evolution-Token`.
4. Escaneie o QR Code exibido: **WhatsApp no celular → Mais opções → Dispositivos conectados →
   Adicionar dispositivo**.
5. A página atualiza o status automaticamente. Quando mostrar **Conectado**, o número está pronto.

Usuários `manager` e `sales_rep` recebem `403` nessa tela e no endpoint de QR.

### 4. Uso

- Painel: menu lateral → **WhatsApp** abre a inbox com a lista de conversas e a conversa selecionada
  (`/whatsapp`). Também é possível abrir uma conversa específica em `/whatsapp/{contact}` ou pelo
  detalhe do contato → **Conversa no WhatsApp**.
- A inbox mostra os contatos do responsável definido no cadastro (`Responsável`). Usuários `admin` e
  `manager` veem todas as conversas, inclusive contatos sem responsável. Novos contatos criados pelo
  painel recebem automaticamente o usuário logado como responsável.
- API (Sanctum):
  - `GET /api/v1/contacts/{contact}/whatsapp-messages`
  - `POST /api/v1/contacts/{contact}/whatsapp-messages` com `{"body": "..."}`

Mensagens recebidas são ligadas ao contato pelo número cadastrado em `whatsapp`. Quando o número
ainda não existe, o webhook cria o contato automaticamente na empresa sistema **WhatsApp (sistema)**
com o primeiro administrador como responsável; o nome vem do `pushName` do WhatsApp e, na ausência
dele, do próprio número. Assim o cliente novo já aparece na inbox e pode receber uma resposta humana.
O webhook atualiza os status (`sent`, `delivered`, `read`). Mensagens `fromMe` enviadas pelo próprio
aparelho aparecem na conversa como outbound, sem usuário do CRM associado; o `provider_message_id`
único evita duplicar o que já foi enviado pelo sistema. Mensagens de grupo continuam sendo ignoradas.
Mensagens de mídia aparecem na conversa como placeholder
(`[Áudio]`, `[Imagem]`, `[Vídeo]`, `[Documento]`, `[Figurinha]`); o arquivo não é baixado. O
`provider_message_id` único evita duplicidade no histórico e o ranking de status evita downgrade
quando eventos chegam fora de ordem.

> O envio continua enfileirado e sem retry automático do POST: como uma resposta perdida pode
> fazer o cliente receber a mesma mensagem duas vezes.

## License
