package com.eventosescolares.app.data

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path

/** Endpoints do backend PHP (base: .../ProjetoBackEndPHP/api/). */
interface ApiService {

    // --- Auth ---
    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): Response<TokenResponse>

    @GET("auth/me")
    suspend fun me(@Header("Authorization") bearer: String): Response<MeResponse>

    @POST("auth/register/aluno")
    suspend fun registerAluno(@Body body: RegisterRequest): Response<Pessoa>

    @POST("auth/register/coordenador")
    suspend fun registerCoordenador(@Body body: RegisterRequest): Response<Pessoa>

    @POST("auth/register/diretor")
    suspend fun registerDiretor(@Body body: RegisterRequest): Response<Pessoa>

    // --- Eventos ---
    @GET("eventos")
    suspend fun eventos(): Response<List<Evento>>

    @GET("eventos/meus-eventos")
    suspend fun meusEventosDiretor(): Response<List<Evento>>

    @GET("eventos/meus-eventos-coordenador")
    suspend fun meusEventosCoordenador(): Response<List<Evento>>

    @GET("eventos/{id}")
    suspend fun evento(@Path("id") id: Int): Response<Evento>

    @POST("eventos")
    suspend fun criarEvento(@Body body: EventoRequest): Response<Evento>

    @PUT("eventos/{id}")
    suspend fun atualizarEvento(@Path("id") id: Int, @Body body: EventoRequest): Response<Evento>

    @DELETE("eventos/{id}")
    suspend fun excluirEvento(@Path("id") id: Int): Response<Unit>

    // --- Inscrições ---
    @POST("inscricoes/eventos/{eventoId}")
    suspend fun inscrever(@Path("eventoId") eventoId: Int): Response<Inscricao>

    @GET("inscricoes/minhas")
    suspend fun minhasInscricoes(): Response<List<Inscricao>>

    @DELETE("inscricoes/{inscricaoId}")
    suspend fun cancelarInscricao(@Path("inscricaoId") id: Int): Response<Unit>

    @GET("inscricoes/eventos/{eventoId}/alunos")
    suspend fun inscritos(@Path("eventoId") eventoId: Int): Response<List<Inscricao>>

    @PATCH("inscricoes/{inscricaoId}/confirmar-presenca")
    suspend fun confirmarPresenca(
        @Path("inscricaoId") id: Int,
        @Body body: PresencaRequest,
    ): Response<Inscricao>
}
