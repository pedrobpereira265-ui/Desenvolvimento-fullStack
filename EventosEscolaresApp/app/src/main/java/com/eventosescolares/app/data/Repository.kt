package com.eventosescolares.app.data

import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.jsonObject
import retrofit2.Response
import java.io.IOException

sealed interface ApiResult<out T> {
    data class Ok<T>(val data: T) : ApiResult<T>

    /** [fieldErrors] vem de {"errors": {campo: mensagem}} nas respostas 400 da API. */
    data class Err(
        val message: String,
        val code: Int = 0,
        val fieldErrors: Map<String, String> = emptyMap(),
    ) : ApiResult<Nothing>
}

/**
 * Único ponto de acesso à API: traduz respostas HTTP em [ApiResult],
 * lê as mensagens de erro do backend e derruba a sessão em caso de 401.
 */
class Repository(
    private val apiProvider: () -> ApiService,
    private val store: SessionStore,
) {
    private val api: ApiService get() = apiProvider()

    // ---------- Auth ----------

    suspend fun login(email: String, senha: String): ApiResult<Session> {
        val token = when (val r = call { api.login(LoginRequest(email.trim(), senha)) }) {
            is ApiResult.Err -> return r
            is ApiResult.Ok -> r.data
        }
        // O login só devolve token/role/nome; o id do usuário vem de /auth/me.
        val me = when (val r = call { api.me("Bearer ${token.token}") }) {
            is ApiResult.Err -> return r
            is ApiResult.Ok -> r.data
        }
        val session = Session(
            token = token.token,
            userId = me.userId.toIntOrNull() ?: 0,
            role = me.role,
            nome = me.nome,
        )
        store.save(session)
        return ApiResult.Ok(session)
    }

    suspend fun register(
        role: String,
        nome: String,
        email: String,
        senha: String,
        ra: String,
    ): ApiResult<Pessoa> = when (role) {
        Roles.ALUNO -> call { api.registerAluno(RegisterRequest(nome.trim(), email.trim(), senha, ra.trim())) }
        Roles.COORDENADOR -> call { api.registerCoordenador(RegisterRequest(nome.trim(), email.trim(), senha)) }
        else -> call { api.registerDiretor(RegisterRequest(nome.trim(), email.trim(), senha)) }
    }

    fun logout() = store.clear()

    // ---------- Eventos ----------

    suspend fun eventos(apenasMeus: Boolean): ApiResult<List<Evento>> {
        if (!apenasMeus) return call { api.eventos() }
        return when (store.session.value?.role) {
            Roles.DIRETOR -> call { api.meusEventosDiretor() }
            Roles.COORDENADOR -> call { api.meusEventosCoordenador() }
            else -> call { api.eventos() }
        }
    }

    suspend fun evento(id: Int) = call { api.evento(id) }

    suspend fun salvarEvento(id: Int?, body: EventoRequest): ApiResult<Evento> =
        if (id == null) call { api.criarEvento(body) } else call { api.atualizarEvento(id, body) }

    suspend fun excluirEvento(id: Int) = call { api.excluirEvento(id) }

    // ---------- Inscrições ----------

    suspend fun inscrever(eventoId: Int) = call { api.inscrever(eventoId) }

    suspend fun minhasInscricoes() = call { api.minhasInscricoes() }

    suspend fun cancelarInscricao(id: Int) = call { api.cancelarInscricao(id) }

    suspend fun inscritos(eventoId: Int) = call { api.inscritos(eventoId) }

    suspend fun confirmarPresenca(inscricaoId: Int, confirmada: Boolean) =
        call { api.confirmarPresenca(inscricaoId, PresencaRequest(confirmada)) }

    // ---------- Infra ----------

    private suspend fun <T : Any> call(block: suspend () -> Response<T>): ApiResult<T> {
        return try {
            val response = block()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null) ApiResult.Ok(body) else ApiResult.Err("Resposta vazia do servidor.", response.code())
            } else {
                parseError(response)
            }
        } catch (e: IOException) {
            ApiResult.Err("Não foi possível conectar ao servidor. Verifique a URL da API e se o Apache/MySQL estão ligados.")
        } catch (e: Exception) {
            ApiResult.Err("Erro inesperado: ${e.message ?: e::class.java.simpleName}")
        }
    }

    private fun parseError(response: Response<*>): ApiResult.Err {
        val code = response.code()
        val raw = runCatching { response.errorBody()?.string() }.getOrNull()
        val obj = runCatching { raw?.let { Network.json.parseToJsonElement(it).jsonObject } }.getOrNull()

        val message = obj?.get("message")?.let { (it as? JsonPrimitive)?.contentOrNull }
            ?: when (code) {
                401 -> "Sessão expirada ou credenciais inválidas."
                403 -> "Seu perfil não tem permissão para essa ação."
                404 -> "Não encontrado."
                409 -> "Conflito: o registro já existe ou o evento está lotado."
                else -> "Erro $code no servidor."
            }

        val fields = (obj?.get("errors") as? JsonObject)?.mapValues { (_, v) ->
            when (v) {
                is JsonArray -> v.joinToString(" ") { (it as? JsonPrimitive)?.contentOrNull ?: it.toString() }
                is JsonPrimitive -> v.contentOrNull ?: ""
                else -> v.toString()
            }
        } ?: emptyMap()

        // Token expirado/inválido em rota protegida: encerra a sessão (o app volta ao login).
        if (code == 401 && store.session.value != null) {
            store.clear()
        }
        return ApiResult.Err(message, code, fields)
    }
}
