package com.eventosescolares.app.ui.inscricoes

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.eventosescolares.app.App
import com.eventosescolares.app.data.ApiResult
import com.eventosescolares.app.data.Inscricao
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.launch

class MinhasInscricoesViewModel(application: Application) : AndroidViewModel(application) {

    private val repo = (application as App).repository

    var inscricoes by mutableStateOf<List<Inscricao>>(emptyList())
        private set
    var loading by mutableStateOf(true)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    val messages = MutableSharedFlow<String>(extraBufferCapacity = 4)

    fun load() {
        viewModelScope.launch {
            loading = true
            when (val r = repo.minhasInscricoes()) {
                is ApiResult.Ok -> {
                    inscricoes = r.data
                    error = null
                }
                is ApiResult.Err -> error = r.message
            }
            loading = false
        }
    }

    fun cancelar(inscricao: Inscricao) {
        viewModelScope.launch {
            when (val r = repo.cancelarInscricao(inscricao.id)) {
                is ApiResult.Ok -> {
                    inscricoes = inscricoes.filterNot { it.id == inscricao.id }
                    messages.tryEmit("Inscrição cancelada.")
                }
                is ApiResult.Err -> messages.tryEmit(r.message)
            }
        }
    }
}
