package com.eventosescolares.app.data

import kotlinx.serialization.json.Json
import okhttp3.HttpUrl.Companion.toHttpUrlOrNull
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import java.util.concurrent.TimeUnit

object Network {

    val json = Json {
        ignoreUnknownKeys = true   // a API pode devolver campos extras (ex.: "inscricoes")
        explicitNulls = false      // não envia campos null (ex.: "ra" para Coordenador)
        coerceInputValues = true
    }

    fun create(baseUrl: String, tokenProvider: () -> String?): ApiService {
        val client = OkHttpClient.Builder()
            .connectTimeout(15, TimeUnit.SECONDS)
            .readTimeout(20, TimeUnit.SECONDS)
            .addInterceptor { chain ->
                val original = chain.request()
                val builder = original.newBuilder().header("Accept", "application/json")
                val token = tokenProvider()
                // Só injeta o token se a chamada não definiu Authorization (caso do /auth/me no login)
                if (token != null && original.header("Authorization") == null) {
                    builder.header("Authorization", "Bearer $token")
                }
                chain.proceed(builder.build())
            }
            .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
            .build()

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json; charset=utf-8".toMediaType()))
            .build()
            .create(ApiService::class.java)
    }

    /** Normaliza o texto digitado: exige http(s) e garante a "/" final. Retorna null se inválida. */
    fun normalizeBaseUrl(raw: String): String? {
        var url = raw.trim()
        if (url.isEmpty()) return null
        if (!url.startsWith("http://") && !url.startsWith("https://")) url = "http://$url"
        if (!url.endsWith("/")) url += "/"
        return if (url.toHttpUrlOrNull() != null) url else null
    }
}
