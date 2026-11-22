package com.eventosescolares.app.data

import kotlinx.serialization.Serializable

/** Perfis aceitos pela API (claim "role" do JWT). */
object Roles {
    const val ALUNO = "Aluno"
    const val COORDENADOR = "Coordenador"
    const val DIRETOR = "Diretor"
}

// ---------- Requisições ----------

@Serializable
data class LoginRequest(val email: String, val senha: String)

/** "ra" só é enviado no cadastro de Aluno (null = omitido do JSON). */
@Serializable
data class RegisterRequest(
    val nome: String,
    val email: String,
    val senha: String,
    val ra: String? = null,
)

@Serializable
data class EventoRequest(
    val nomeEvento: String,
    val dataEvento: String, // "yyyy-MM-dd HH:mm:ss" em UTC
    val local: String,
    val vagasMaximas: Int,
)

@Serializable
data class PresencaRequest(val presencaConfirmada: Boolean)

// ---------- Respostas ----------

@Serializable
data class TokenResponse(val token: String, val role: String, val nome: String)

@Serializable
data class MeResponse(val userId: String, val role: String, val nome: String)

/** Aluno, Coordenador ou Diretor (a API devolve a mesma forma básica). */
@Serializable
data class Pessoa(
    val id: Int = 0,
    val nome: String = "",
    val email: String? = null,
    val ra: String? = null,
    val criadoEm: String? = null,
)

@Serializable
data class Evento(
    val id: Int,
    val nomeEvento: String,
    val dataEvento: String, // ISO-8601 UTC, ex.: 2026-10-15T19:00:00Z
    val local: String = "",
    val vagasMaximas: Int = 0,
    val criadoEm: String? = null,
    val coordenadorId: Int? = null,
    val coordenador: Pessoa? = null,
    val diretorId: Int? = null,
    val diretor: Pessoa? = null,
) {
    val organizador: String
        get() = coordenador?.nome ?: diretor?.nome ?: "Não informado"
}

@Serializable
data class Inscricao(
    val id: Int,
    val alunoId: Int = 0,
    val aluno: Pessoa? = null,
    val eventoId: Int = 0,
    val evento: Evento? = null,
    val presencaConfirmada: Boolean = false,
    val inscritoEm: String? = null,
)

// ---------- Sessão local ----------

data class Session(
    val token: String,
    val userId: Int,
    val role: String,
    val nome: String,
) {
    val isGestor: Boolean get() = role == Roles.COORDENADOR || role == Roles.DIRETOR
    val isAluno: Boolean get() = role == Roles.ALUNO

    /** Mesma regra do front PHP (can_manage): só o dono gerencia o evento. */
    fun podeGerenciar(e: Evento): Boolean = when (role) {
        Roles.COORDENADOR -> e.coordenadorId == userId
        Roles.DIRETOR -> e.diretorId == userId
        else -> false
    }
}
