# Sistema de Gestão de Eventos Escolares — Backend PHP (MySQL + JWT)

Backend de API REST para gestão de eventos escolares, inscrições de alunos e controle de presença, migrado do C# (.NET 8) para **PHP 8+** utilizando **MySQL** e **Autenticação JWT**, pré-configurado para execução imediata no **XAMPP**.

---


## 🚀 Tecnologias Utilizadas

- **Linguagem:** PHP 8.0+ / 8.1+ / 8.2+
- **Servidor Web:** Apache (XAMPP) ou Servidor Embutido do PHP
- **Banco de Dados:** MySQL 5.7+ / MariaDB 10.4+ / MySQL 8.0+ (via PDO)
- **Autenticação:** JSON Web Token (JWT) com assinatura HMAC-SHA256 (HS256)
  - Compatível com biblioteca `firebase/php-jwt` via Composer
  - Possui **mecanismo nativo fallback** (RFC 7519), rodando sem necessidade de instalar dependências externas via Composer!
- **Criptografia de Senha:** BCrypt (`password_hash` com `PASSWORD_BCRYPT`), 100% compatível com a biblioteca `BCrypt.Net` do projeto C# original.

---

## 📋 Resolução da Inconsistência de Permissões (Diretor vs Coordenador)

Na versão original em C#, havia uma divergência entre a camada de serviço (`EventoService`) e a camada de controle (`EventosController`):
- O `EventoService` continha regras explícitas para vincular e autorizar tanto `Coordenador` quanto `Diretor` a gerenciar eventos (`AssignOwner` e `EnsureCanManage`).
- No entanto, o `EventosController` possuía a anotação `[Authorize(Roles = AppRoles.Coordenador)]` nos métodos `Create`, `Update` e `Delete`, impedindo que o Diretor criasse eventos através da API, mesmo existindo a rota `/api/eventos/meus-eventos` exclusiva para Diretores.

### Decisão Adotada: Opção B (Alinhamento com a Regra de Negócio)
- Foi unificada a permissão para `Gestores` (`Coordenador` e `Diretor`) nas rotas de criação, edição e exclusão de eventos (`POST /api/eventos`, `PUT /api/eventos/{id}`, `DELETE /api/eventos/{id}`).
- Ao criar o evento, o sistema atribui automaticamente `ID_COORDENADOR` se o criador for Coordenador, ou `ID_DIRETOR` se for Diretor.
- As operações de atualização (`PUT`) e exclusão (`DELETE`) verificam estritamente o dono: um Coordenador só altera eventos onde `ID_COORDENADOR == userId`, e um Diretor só altera eventos onde `ID_DIRETOR == userId`. Tentativas de alterar eventos alheios retornam `403 Forbidden`.

---

## ⚙️ Instalação e Configuração no XAMPP

### 1. Iniciar os Serviços no XAMPP
1. Abra o **XAMPP Control Panel**.
2. Clique no botão **Start** ao lado de **Apache**.
3. Clique no botão **Start** ao lado de **MySQL**.

### 2. Copiar o Projeto para o XAMPP
Copie a pasta `ProjetoBackEndPHP` para o diretório `htdocs` do seu XAMPP:
```
C:\xampp\htdocs\ProjetoBackEndPHP
```

---

## 🗄️ Criação do Banco de Dados e Importação das Tabelas

Você pode criar o banco de duas formas:

### Opção 1: Via phpMyAdmin (Interface Gráfica — Recomendado)
1. Acesse o phpMyAdmin no navegador: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. No menu superior, clique na aba **Importar** (ou crie a base `gestao_eventos_escolares` na aba *Bancos de dados*).
3. Clique em **Escolher arquivo** e selecione o arquivo:
   `C:\xampp\htdocs\ProjetoBackEndPHP\database\schema.sql`
4. Clique no botão **Importar** no final da página. Todas as 5 tabelas (`ALUNO`, `COORDENADOR`, `DIRETOR`, `EVENTO`, `INSCRICAO`), constraints e índices serão criados automaticamente.
5. (Opcional) Para carregar dados iniciais de teste (usuários e eventos com senha `123456`), importe também:
   `C:\xampp\htdocs\ProjetoBackEndPHP\database\seed.sql`

### Opção 2: Via Terminal (Prompt de Comando / PowerShell)
Abra o terminal e execute:
```bash
# Executa o script DDL de criação
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\ProjetoBackEndPHP\database\schema.sql"

# (Opcional) Executa os dados de teste (seed)
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\ProjetoBackEndPHP\database\seed.sql"
```

---

## 🔌 Configuração da Conexão com o Banco

As credenciais do banco estão centralizadas em `config/Database.php`.
Por padrão, o arquivo já está configurado para os padrões do XAMPP:
- **Host:** `localhost`
- **Porta:** `3306`
- **Banco de Dados:** `gestao_eventos_escolares`
- **Usuário:** `root`
- **Senha:** *(vazio)*

Caso seu MySQL local utilize senha ou porta diferente, basta definir variáveis de ambiente ou editar diretamente o arquivo `config/Database.php`.

---

## 🏃 Como Executar a API

### Forma 1: Pelo Apache do XAMPP (URL Direta)
Com a pasta em `C:\xampp\htdocs\ProjetoBackEndPHP`, a API responde diretamente em:
```
http://localhost/ProjetoBackEndPHP/api
```
Exemplo de requisição de login:
```
POST http://localhost/ProjetoBackEndPHP/api/auth/login
```

### Forma 2: Pelo Servidor Embutido do PHP (CLI)
Você também pode rodar a API usando o executável do PHP do XAMPP sem depender de VirtualHosts:
```bash
cd "C:\xampp\htdocs\ProjetoBackEndPHP"
C:\xampp\php\php.exe -S localhost:5000 -t public
```
A API ficará disponível em:
```
http://localhost:5000/api
```

---

## 📁 Estrutura de Diretórios

```
ProjetoBackEndPHP/
├── config/
│   ├── Database.php              # Conexão PDO com MySQL (padrão XAMPP)
│   └── JwtConfig.php             # Configurações de chave, emissor e expiração do JWT
├── database/
│   ├── schema.sql                # Script DDL completo para MySQL
│   └── seed.sql                  # Dados de teste pré-cadastrados (senha: 123456)
├── public/
│   ├── .htaccess                 # Reescrita Apache e repasse de Authorization Bearer
│   └── index.php                 # Front Controller da aplicação e mapeamento de rotas
├── src/
│   ├── bootstrap.php             # Configuração inicial, fuso horário UTC e autoloader PSR-4
│   ├── Router.php                # Roteador HTTP tolerante a subpastas do Apache e tratamento de erros
│   ├── Common/
│   │   ├── AppClaims.php         # Constantes: userId, role, nome
│   │   └── AppRoles.php          # Constantes: Aluno, Coordenador, Diretor, Gestores
│   ├── Controllers/
│   │   ├── AuthController.php    # Cadastro, login e validação do token (/me)
│   │   ├── EventosController.php # CRUD e listagens de eventos escolares
│   │   └── InscricoesController.php # Inscrições, cancelamentos e confirmação de presença
│   ├── DTOs/
│   │   ├── Auth/
│   │   │   ├── LoginDto.php
│   │   │   ├── RegisterAlunoDto.php
│   │   │   ├── RegisterCoordenadorDto.php
│   │   │   ├── RegisterDiretorDto.php
│   │   │   └── TokenResponseDto.php
│   │   ├── Eventos/
│   │   │   ├── CreateEventoDto.php
│   │   │   └── UpdateEventoDto.php
│   │   └── Inscricoes/
│   │       └── ConfirmarPresencaDto.php
│   ├── Exceptions/
│   │   ├── ConflictException.php       # HTTP 409 (Email/RA duplicado, evento lotado)
│   │   ├── ForbiddenException.php      # HTTP 403 (Permissão negada / Não é dono)
│   │   ├── NotFoundException.php       # HTTP 404 (Recurso inexistente)
│   │   ├── UnauthorizedException.php   # HTTP 401 (Credenciais ou token inválidos)
│   │   └── ValidationException.php     # HTTP 400 (Campos inválidos)
│   ├── Middleware/
│   │   ├── AuthMiddleware.php    # Validação do Bearer Token e verificação de Roles
│   │   └── CorsMiddleware.php    # Cabeçalhos CORS e pré-vôo OPTIONS para o Front-End
│   ├── Models/
│   │   ├── Aluno.php             # Modelo e serialização JSON camelCase
│   │   ├── Coordenador.php
│   │   ├── Diretor.php
│   │   ├── Evento.php
│   │   └── Inscricao.php
│   └── Services/
│       ├── AuthService.php       # Regras de cadastro, unicidade global de e-mail e login unificado
│       ├── EventoService.php     # Regras de eventos, limite de vagas e controle de dono
│       ├── InscricaoService.php  # Regras de inscrição única e presença
│       └── JwtService.php        # Gerador e validador HS256 com fallback nativo
├── tests/
│   └── test_backend.php          # Suíte de testes unitários (executável via CLI)
├── .htaccess                     # Reescrita raiz para subpasta /public
├── composer.json                 # Manifesto Composer com firebase/php-jwt
└── README.md                     # Documentação completa do projeto
```

---

## 🔒 Autenticação e Regras de Segurança (JWT)

- **Algoritmo:** HMAC-SHA256 (`HS256`)
- **Segredo:** `GestaoEscolar@ChaveSecreta#2026!MuitoSegura$512bits`
- **Emissor (Issuer):** `GestaoEventosEscolares`
- **Audiência (Audience):** `GestaoEventosEscolaresClientes`
- **Validade do Token:** 8 horas
- **Claims geradas no payload:**
  - `userId`: ID do usuário autenticado (string)
  - `role`: Perfil do usuário (`Aluno`, `Coordenador`, `Diretor`)
  - `nome`: Nome completo do usuário
  - `http://schemas.xmlsoap.org/ws/2005/05/identity/claims/nameidentifier`: ID
  - `http://schemas.microsoft.com/ws/2008/06/identity/claims/role`: Role
  - `jti`: Identificador único (UUID v4)
  - `iat` e `exp`: Timestamps de emissão e expiração

Nas requisições protegidas, inclua o token no cabeçalho HTTP:
```http
Authorization: Bearer <seu_token_aqui>
```

---

## 📖 Contratos da API (Endpoints)

### 1. Autenticação (`/api/auth`)

| Método | Rota | Autenticação | Descrição |
|---|---|---|---|
| `POST` | `/api/auth/register/diretor` | Pública | Cadastra um novo Diretor (`201 Created` / `409 Conflict`) |
| `POST` | `/api/auth/register/coordenador` | Pública | Cadastra um novo Coordenador (`201 Created` / `409 Conflict`) |
| `POST` | `/api/auth/register/aluno` | Pública | Cadastra um novo Aluno (`201 Created` / `409 Conflict`) |
| `POST` | `/api/auth/login` | Pública | Login unificado (Aluno -> Coordenador -> Diretor) |
| `GET` | `/api/auth/me` | Bearer Token | Retorna os dados (`userId`, `role`, `nome`) do token |

#### Exemplo de Payload de Login:
```json
POST /api/auth/login
Content-Type: application/json

{
  "email": "coordenador@escola.com",
  "senha": "123456"
}
```
**Resposta (200 OK):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "role": "Coordenador",
  "nome": "Profa. Ana Coordenadora"
}
```

---

### 2. Eventos (`/api/eventos`)

| Método | Rota | Permissão (Role) | Descrição |
|---|---|---|---|
| `GET` | `/api/eventos` | Qualquer autenticado | Lista todos os eventos com coordenador e diretor vinculados |
| `GET` | `/api/eventos/{id}` | Qualquer autenticado | Retorna detalhes de um evento específico |
| `GET` | `/api/eventos/meus-eventos` | `Diretor` | Lista os eventos criados pelo diretor logado |
| `GET` | `/api/eventos/meus-eventos-coordenador` | `Coordenador` | Lista os eventos criados pelo coordenador logado |
| `POST` | `/api/eventos` | `Gestores` (`Diretor,Coordenador`) | Cria novo evento (vincula dono automaticamente) |
| `PUT` | `/api/eventos/{id}` | `Gestores` (Somente o Dono) | Atualiza evento existente |
| `DELETE` | `/api/eventos/{id}` | `Gestores` (Somente o Dono) | Exclui evento (`204 No Content`) |

#### Exemplo de Criação de Evento:
```json
POST /api/eventos
Authorization: Bearer <token_de_coordenador_ou_diretor>
Content-Type: application/json

{
  "nomeEvento": "Semana Acadêmica de Tecnologia",
  "dataEvento": "2026-10-15 19:00:00",
  "local": "Auditório Nobre",
  "vagasMaximas": 120
}
```

---

### 3. Inscrições (`/api/inscricoes`)

| Método | Rota | Permissão (Role) | Descrição |
|---|---|---|---|
| `POST` | `/api/inscricoes/eventos/{eventoId}` | `Aluno` | Inscreve o aluno autenticado no evento |
| `GET` | `/api/inscricoes/minhas` | `Aluno` | Lista todas as inscrições do aluno logado |
| `DELETE` | `/api/inscricoes/{inscricaoId}` | `Aluno` (Próprio Aluno) | Cancela a própria inscrição (`204 No Content`) |
| `GET` | `/api/inscricoes/eventos/{eventoId}/alunos` | `Gestores` | Lista todos os alunos inscritos em um evento |
| `GET` | `/api/inscricoes/eventos/{eventoId}/presenca-confirmada` | `Gestores` | Lista alunos com presença confirmada no evento |
| `PATCH` | `/api/inscricoes/{inscricaoId}/confirmar-presenca` | `Gestores` | Confirma ou revoga presença de um aluno |

#### Exemplo de Confirmação de Presença:
```json
PATCH /api/inscricoes/1/confirmar-presenca
Authorization: Bearer <token_de_gestor>
Content-Type: application/json

{
  "presencaConfirmada": true
}
```

---

## 🧪 Como Executar os Testes Unitários

O backend inclui uma suíte automatizada de testes que valida o `JwtService`, o tratamento de claims, a rejeição de tokens alterados e as validações de todos os DTOs:

```bash
C:\xampp\php\php.exe tests/test_backend.php
```

Resultado esperado:
```
======================================================
 TESTES UNITÁRIOS E DE VALIDAÇÃO DO BACKEND PHP 
======================================================

--- Testando JwtService ---
[PASS] Token JWT gerado com sucesso (não vazio)
[PASS] Payload contém claim userId = 42
[PASS] Payload contém claim role = Coordenador
[PASS] Payload contém claim nome = Coordenador Teste
[PASS] Token adulterado rejeitado corretamente com UnauthorizedException

--- Testando DTOs e Validações ---
[PASS] RegisterAlunoDto criado com dados válidos
[PASS] RegisterAlunoDto com senha curta lançou ValidationException
[PASS] RegisterAlunoDto com email inválido lançou ValidationException
[PASS] CreateEventoDto criado com sucesso
[PASS] CreateEventoDto com vagas <= 0 lançou ValidationException
[PASS] ConfirmarPresencaDto booleano true
[PASS] ConfirmarPresencaDto booleano false

======================================================
 RESULTADO: 12 passaram, 0 falharam.
======================================================
```

---

## Variáveis de ambiente

A aplicação lê as configurações via `getenv()`. Todas têm um padrão seguro para o
ambiente local (XAMPP), então o projeto roda sem configurar nada.

| Variável | Padrão | Descrição |
|---|---|---|
| `DB_HOST` | `localhost` | Host do MySQL |
| `DB_PORT` | `3306` | Porta do MySQL |
| `DB_NAME` | `gestao_eventos_escolares` | Nome da base |
| `DB_USER` | `root` | Usuário do MySQL |
| `DB_PASS` | *(vazio)* | Senha do MySQL |
| `JWT_SECRET` | *(fallback de dev)* | **Obrigatória em produção.** Chave de assinatura HS256 |
| `APP_DEBUG` | `false` | Se `true`, os erros 500 retornam a mensagem técnica no JSON |

### Em produção

Defina `JWT_SECRET` com um valor longo e aleatório e deixe `APP_DEBUG` desligado.
Sem `JWT_SECRET`, a API usa uma chave de desenvolvimento conhecida e qualquer pessoa
com acesso ao código consegue forjar um token de Diretor.

Gerando uma chave:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Linux/macOS:

```bash
export JWT_SECRET="cole_a_chave_aqui"
export APP_DEBUG=false
```

Windows (PowerShell):

```powershell
$env:JWT_SECRET="cole_a_chave_aqui"
```

No XAMPP, também é possível definir em `httpd.conf`:

```apache
SetEnv JWT_SECRET "cole_a_chave_aqui"
```

## Requisitos de extensões PHP

`mbstring`, `pdo`, `pdo_mysql` e `json`. No XAMPP já vêm habilitadas. Em um servidor
Linux, instale com:

```bash
sudo apt install php-mbstring php-mysql
```

Verifique com `php -m`.

---

## 🖥️ Front End (PHP)

O projeto inclui um Front End em PHP + CSS/JS na pasta `frontend/`, que consome esta API.
Com o XAMPP rodando, acesse **http://localhost/ProjetoBackEndPHP/frontend/**.
Detalhes, telas e execução com o servidor embutido: veja [`frontend/README.md`](frontend/README.md).
