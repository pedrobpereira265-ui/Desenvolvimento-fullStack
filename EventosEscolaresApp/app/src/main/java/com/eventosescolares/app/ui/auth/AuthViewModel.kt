package com.eventosescolares.app.ui.auth

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.eventosescolares.app.App
import com.eventosescolares.app.data.ApiResult
import com.eventosescolares.app.data.Roles
import kotlinx.coroutines.launch

class AuthViewModel(application: Application) : AndroidViewModel(application) {

    private val app = application as App
    private val repo = app.repository

    var loading by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    var fieldErrors by mutableStateOf<Map<String, String>>(emptyMap())
        private set

    var serverUrl by mutableStateOf(app.sessionStore.baseUrl)

    fun clearErrors() {
        error = null
        fieldErrors = emptyMap()
    }

    fun saveServer(): Boolean {
        val normalized = com.eventosescolares.app.data.Network.normalizeBaseUrl(serverUrl)
        return if (normalized == null) {
            error = "URL do servidor inválida. Ex.: http://192.168.0.10/ProjetoBackEndPHP/api/"
            false
        } else {
            serverUrl = normalized
            app.changeBaseUrl(normalized)
            error = null
            true
        }
    }

    /** Em caso de sucesso a sessão é salva e o app troca de tela sozinho (AppRoot observa a sessão). */
    fun login(email: String, senha: String) {
        clearErrors()
        if (email.isBlank() || senha.isBlank()) {
            error = "Informe e-mail e senha."
            return
        }
        viewModelScope.launch {
            loading = true
            val r = repo.login(email, senha)
            if (r is ApiResult.Err) {
                error = r.message
                fieldErrors = r.fieldErrors
            }
            loading = false
        }
    }

    fun register(role: String, nome: String, email: String, ra: String, senha: String) {
        clearErrors()
        val local = mutableMapOf<String, String>()
        if (nome.isBlank()) local["nome"] = "O nome é obrigatório."
        if (email.isBlank()) local["email"] = "O e-mail é obrigatório."
        if (role == Roles.ALUNO && ra.isBlank()) local["ra"] = "O RA é obrigatório."
        if (senha.length < 6) local["senha"] = "A senha deve ter no mínimo 6 caracteres."
        if (local.isNotEmpty()) {
            fieldErrors = local
            return
        }
        viewModelScope.launch {
            loading = true
            when (val r = repo.register(role, nome, email, senha, ra)) {
                is ApiResult.Err -> {
                    error = r.message
                    fieldErrors = r.fieldErrors
                }
                // Cadastro ok: já entra com as mesmas credenciais
                is ApiResult.Ok -> {
                    val login = repo.login(email, senha)
                    if (login is ApiResult.Err) error = "Cadastro feito, mas o login falhou: ${login.message}"
                }
            }
            loading = false
        }
    }
}
