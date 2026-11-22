package com.eventosescolares.app

import android.app.Application
import com.eventosescolares.app.data.ApiService
import com.eventosescolares.app.data.Network
import com.eventosescolares.app.data.Repository
import com.eventosescolares.app.data.SessionStore

/** Container simples de dependências (sem Hilt, para manter o projeto enxuto). */
class App : Application() {

    lateinit var sessionStore: SessionStore
        private set
    lateinit var repository: Repository
        private set

    private var api: ApiService? = null

    override fun onCreate() {
        super.onCreate()
        sessionStore = SessionStore(this)
        repository = Repository(apiProvider = { currentApi() }, store = sessionStore)
    }

    @Synchronized
    private fun currentApi(): ApiService =
        api ?: Network.create(sessionStore.baseUrl) { sessionStore.token }.also { api = it }

    /** Chamado quando o usuário altera a URL do servidor na tela de login. */
    @Synchronized
    fun changeBaseUrl(url: String) {
        sessionStore.baseUrl = url
        api = null
    }
}
