# Front End PHP — Eventos Escolares

Front End em **PHP puro** (sem frameworks) com **CSS/JavaScript** próprios, que consome a API REST do Back End
(`/api/...`). A sessão guarda o token JWT; todas as chamadas à API são feitas no servidor via cURL.

## Telas

| Arquivo | Quem acessa | O que faz |
|---|---|---|
| `login.php` / `registrar.php` | Público | Login e cadastro (Aluno, Coordenador ou Diretor) |
| `eventos.php` | Todos | Painel + lista de eventos com filtros (próximos/encerrados/todos/meus) e busca instantânea |
| `evento.php?id=` | Todos | Detalhes; Aluno: inscrever/cancelar; Gestor dono: editar, excluir, ver inscritos |
| `evento_form.php` | Coordenador / Diretor | Criar e editar eventos |
| `minhas_inscricoes.php` | Aluno | Lista e cancelamento das próprias inscrições |
| `inscritos.php?evento=` | Coordenador / Diretor | Inscritos, confirmar/revogar presença, exportar CSV |
| `acoes.php` | — | Processa as ações POST (padrão Post/Redirect/Get) |

## Como executar

### XAMPP (recomendado)
1. Suba **Apache** e **MySQL**, importe `database/schema.sql` e `database/seed.sql` (veja o README principal).
2. Com o projeto em `C:\xampp\htdocs\ProjetoBackEndPHP`, acesse:
   **http://localhost/ProjetoBackEndPHP/frontend/**

A URL da API é descoberta automaticamente (`http://localhost/ProjetoBackEndPHP/api`). A extensão `curl` do PHP
já vem habilitada no XAMPP.

### Servidor embutido do PHP (dois terminais)
```bash
# Terminal 1 – API
php -S localhost:5000 -t public
# Terminal 2 – Front End
php -S localhost:8000 -t frontend
```
Acesse **http://localhost:8000**. (Use dois servidores: o servidor embutido sem workers atende uma requisição por vez
e o Front chama a API durante cada página.) Para apontar para outra API, defina `FRONT_API_URL`:
```bash
FRONT_API_URL="http://localhost:5000/api" php -S localhost:8000 -t frontend
```

## Usuários de teste (senha `123456`)
- Aluno: `joao.aluno@escola.com`
- Coordenador: `coordenador@escola.com`
- Diretor: `diretor@escola.com`

## Segurança e qualidade
- Escape de toda saída HTML (`e()`), proteção **CSRF** em todos os formulários, `session_regenerate_id` no login,
  cookie de sessão `HttpOnly` + `SameSite=Lax`, proteção contra *open redirect* e contra injeção de fórmulas no CSV.
- Token expirado (401) encerra a sessão e leva ao login; erros da API (400/403/404/409) aparecem como mensagens amigáveis.
- Datas: a API trabalha em UTC; o Front exibe e recebe no fuso `America/Sao_Paulo` (`includes/config.php`).
- Layout responsivo, tema claro/escuro automático, acessível (foco visível, `aria-*`, *skip link*) e sem dependências externas.

## Estrutura
```
frontend/
├── assets/css/style.css, assets/js/app.js
├── includes/ (config, ApiClient, bootstrap/helpers, components) — bloqueado por .htaccess
└── *.php (páginas)
```

## Correção feita no Back End
`InscricaoService::getByEvento` e `getPresencaConfirmada` devolviam `aluno: null` porque o SQL usava os nomes
`NOME_ALUNO/EMAIL_ALUNO/RA_ALUNO`, mas o model `Inscricao` lê `ALUNO_NOME/ALUNO_EMAIL/ALUNO_RA`. Foram adicionados
*aliases* no SQL, permitindo exibir nome, e-mail e RA dos inscritos.
