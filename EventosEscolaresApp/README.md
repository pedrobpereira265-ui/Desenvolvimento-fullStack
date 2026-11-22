# App Android — Eventos Escolares (Kotlin + Jetpack Compose)

Front End Android que consome a API REST em PHP do projeto **ProjetoBackEndPHP** (mesmo contrato usado pelo Front End PHP).
O backend **não foi alterado**.

## Stack
Kotlin 2.0 · Jetpack Compose (Material 3) · Navigation Compose · MVVM (ViewModel + StateFlow/State) · Retrofit 2 + OkHttp · kotlinx.serialization · JWT guardado em SharedPreferences.

## Funcionalidades (espelham o front PHP)
| Perfil | O que faz |
|---|---|
| Todos | Login, cadastro (Aluno / Coordenador / Diretor), lista e detalhes de eventos, sair |
| Aluno | Inscrever-se, cancelar inscrição, tela "Minhas inscrições" |
| Coordenador / Diretor | Criar evento, filtro "Meus eventos", editar/excluir (somente o dono), ver inscritos e confirmar/revogar presença |

## Como rodar
1. **Backend**: XAMPP com Apache + MySQL ligados, banco importado (`database/schema.sql` e, opcional, `seed.sql`; senha dos usuários de teste: `123456`).
2. Abra esta pasta no **Android Studio** (Ladybug ou mais novo, JDK 17) e aguarde o *Gradle Sync*.
3. **Emulador**: já funciona com a URL padrão `http://10.0.2.2/ProjetoBackEndPHP/api/` (`10.0.2.2` = `localhost` do PC).
4. **Celular físico**: PC e celular na mesma rede Wi‑Fi. Na tela de login toque em *Configurar servidor* e informe `http://IP_DO_PC/ProjetoBackEndPHP/api/` (ex.: `http://192.168.0.10/ProjetoBackEndPHP/api/`). Libere o Apache no firewall do Windows.
   - Se usar o servidor embutido do PHP, rode com `php -S 0.0.0.0:5000 -t public` e use `http://IP_DO_PC:5000/api/`.
5. A URL fica salva no aparelho; o padrão de fábrica está em `app/build.gradle.kts` (`API_BASE_URL`).

## Estrutura
```
app/src/main/java/com/eventosescolares/app/
├── App.kt / MainActivity.kt
├── data/      Models, ApiService (Retrofit), Network, SessionStore, Repository
├── util/      DateUtils (API em UTC <-> America/Sao_Paulo)
└── ui/        Navigation, HomeScaffold, theme, common, auth, eventos, inscricoes
```

## Pontos de atenção
- **HTTP sem HTTPS**: o manifest tem `usesCleartextTraffic="true"` só para desenvolvimento local. Em produção use HTTPS e remova.
- **Datas**: a API usa UTC; o app converte para o horário de Brasília na exibição e na digitação (`dd/MM/aaaa` e `HH:mm`).
- **Token expirado (401)**: o app encerra a sessão e volta ao login automaticamente.
