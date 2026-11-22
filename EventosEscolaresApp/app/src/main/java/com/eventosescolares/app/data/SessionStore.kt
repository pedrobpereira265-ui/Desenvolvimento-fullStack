package com.eventosescolares.app.data

import android.content.Context
import com.eventosescolares.app.BuildConfig
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow

/** Guarda sessão (token JWT) e URL do servidor em SharedPreferences. */
class SessionStore(context: Context) {

    private val prefs = context.getSharedPreferences("eventos_prefs", Context.MODE_PRIVATE)

    private val _session = MutableStateFlow(load())
    val session: StateFlow<Session?> = _session

    val token: String? get() = _session.value?.token

    var baseUrl: String
        get() = prefs.getString(KEY_URL, null) ?: BuildConfig.API_BASE_URL
        set(value) = prefs.edit().putString(KEY_URL, value).apply()

    fun save(s: Session) {
        prefs.edit()
            .putString(KEY_TOKEN, s.token)
            .putInt(KEY_USER_ID, s.userId)
            .putString(KEY_ROLE, s.role)
            .putString(KEY_NOME, s.nome)
            .apply()
        _session.value = s
    }

    fun clear() {
        prefs.edit()
            .remove(KEY_TOKEN).remove(KEY_USER_ID).remove(KEY_ROLE).remove(KEY_NOME)
            .apply()
        _session.value = null
    }

    private fun load(): Session? {
        val token = prefs.getString(KEY_TOKEN, null) ?: return null
        val role = prefs.getString(KEY_ROLE, null) ?: return null
        return Session(
            token = token,
            userId = prefs.getInt(KEY_USER_ID, 0),
            role = role,
            nome = prefs.getString(KEY_NOME, "") ?: "",
        )
    }

    private companion object {
        const val KEY_TOKEN = "token"
        const val KEY_USER_ID = "user_id"
        const val KEY_ROLE = "role"
        const val KEY_NOME = "nome"
        const val KEY_URL = "base_url"
    }
}
