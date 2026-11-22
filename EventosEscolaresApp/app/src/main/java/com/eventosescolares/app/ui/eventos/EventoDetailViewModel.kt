package com.eventosescolares.app.ui.eventos

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.viewModelScope
import com.eventosescolares.app.App
import com.eventosescolares.app.data.ApiResult
import com.eventosescolares.app.data.Evento
import com.eventosescolares.app.data.Inscricao
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.launch

class EventoDetailViewModel(application: Application, handle: SavedStateHandle) : AndroidViewModel(application) {

    private val repo = (application as App).repository
    private val sessionStore = (application as App).sessionStore
    val id: Int = checkNotNull(handle.get<Int>("id"))

    var evento by mutableStateOf<Evento?>(null)
        private set
    var minhaInscricao by mutableStateOf<Inscricao?>(null)
        private set
    var loading by mutableStateOf(true)
        private set
    var busy by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    /** Mensagens rápidas para o Snackbar. */
    val messages = MutableSharedFlow<String>(extraBufferCapacity = 4)

    fun load() {
        viewModelScope.launch {
            loading = true
            when (val r = repo.evento(id)) {
                is ApiResult.Ok -> {
                    evento = r.data
                    error = null
                }
                is ApiResult.Err -> error = r.message
            }
            if (sessionStore.session.value?.isAluno == true && evento != null) {
                (repo.minhasInscricoes() as? ApiResult.Ok)?.let { ok ->
                    minhaInscricao = ok.data.firstOrNull { it.eventoId == id || it.evento?.id == id }
                }
            }
            loading = false
        }
    }

    fun inscrever() = acao { 
        when (val r = repo.inscrever(id)) {
            is ApiResult.Ok -> {
                minhaInscricao = r.data
                messages.tryEmit("Inscrição realizada!")
            }
            is ApiResult.Err -> messages.tryEmit(r.message)
        }
    }

    fun cancelarInscricao() = acao {
        val insc = minhaInscricao ?: return@acao
        when (val r = repo.cancelarInscricao(insc.id)) {
            is ApiResult.Ok -> {
                minhaInscricao = null
                messages.tryEmit("Inscrição cancelada.")
            }
            is ApiResult.Err -> messages.tryEmit(r.message)
        }
    }

    fun excluir(onDeleted: () -> Unit) = acao {
        when (val r = repo.excluirEvento(id)) {
            is ApiResult.Ok -> onDeleted()
            is ApiResult.Err -> messages.tryEmit(r.message)
        }
    }

    private fun acao(block: suspend () -> Unit) {
        if (busy) return
        viewModelScope.launch {
            busy = true
            block()
            busy = false
        }
    }
}
