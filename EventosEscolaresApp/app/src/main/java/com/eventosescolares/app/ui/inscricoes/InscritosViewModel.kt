package com.eventosescolares.app.ui.inscricoes

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.viewModelScope
import com.eventosescolares.app.App
import com.eventosescolares.app.data.ApiResult
import com.eventosescolares.app.data.Inscricao
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.launch

class InscritosViewModel(application: Application, handle: SavedStateHandle) : AndroidViewModel(application) {

    private val repo = (application as App).repository
    private val eventoId: Int = checkNotNull(handle.get<Int>("id"))

    var nomeEvento by mutableStateOf("")
        private set
    var inscritos by mutableStateOf<List<Inscricao>>(emptyList())
        private set
    var loading by mutableStateOf(true)
        private set
    var error by mutableStateOf<String?>(null)
        private set

    val messages = MutableSharedFlow<String>(extraBufferCapacity = 4)

    val confirmados: Int get() = inscritos.count { it.presencaConfirmada }

    fun load() {
        viewModelScope.launch {
            loading = true
            (repo.evento(eventoId) as? ApiResult.Ok)?.let { nomeEvento = it.data.nomeEvento }
            when (val r = repo.inscritos(eventoId)) {
                is ApiResult.Ok -> {
                    inscritos = r.data
                    error = null
                }
                is ApiResult.Err -> error = r.message
            }
            loading = false
        }
    }

    fun setPresenca(inscricao: Inscricao, confirmada: Boolean) {
        viewModelScope.launch {
            when (val r = repo.confirmarPresenca(inscricao.id, confirmada)) {
                is ApiResult.Ok -> inscritos = inscritos.map {
                    if (it.id == inscricao.id) it.copy(presencaConfirmada = r.data.presencaConfirmada) else it
                }
                is ApiResult.Err -> messages.tryEmit(r.message)
            }
        }
    }
}
