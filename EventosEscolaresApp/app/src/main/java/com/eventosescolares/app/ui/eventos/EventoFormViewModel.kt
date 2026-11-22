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
import com.eventosescolares.app.data.EventoRequest
import com.eventosescolares.app.util.DateUtils
import kotlinx.coroutines.launch

class EventoFormViewModel(application: Application, handle: SavedStateHandle) : AndroidViewModel(application) {

    private val repo = (application as App).repository

    /** id = -1 (padrão da rota) significa "novo evento". */
    private val editId: Int? = handle.get<Int>("id")?.takeIf { it >= 0 }
    val isEdit: Boolean get() = editId != null

    var nome by mutableStateOf("")
    var data by mutableStateOf("")   // dd/MM/yyyy
    var hora by mutableStateOf("")   // HH:mm
    var local by mutableStateOf("")
    var vagas by mutableStateOf("")

    var loading by mutableStateOf(editId != null)
        private set
    var saving by mutableStateOf(false)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    var fieldErrors by mutableStateOf<Map<String, String>>(emptyMap())
        private set

    init {
        if (editId != null) {
            viewModelScope.launch {
                when (val r = repo.evento(editId)) {
                    is ApiResult.Ok -> {
                        val e = r.data
                        nome = e.nomeEvento
                        local = e.local
                        vagas = e.vagasMaximas.toString()
                        val (d, h) = DateUtils.toInputs(e.dataEvento)
                        data = d
                        hora = h
                    }
                    is ApiResult.Err -> error = r.message
                }
                loading = false
            }
        }
    }

    fun salvar(onDone: () -> Unit) {
        error = null
        val errs = mutableMapOf<String, String>()
        if (nome.isBlank()) errs["nomeEvento"] = "O nome do evento é obrigatório."
        if (local.isBlank()) errs["local"] = "O local é obrigatório."
        val vagasInt = vagas.trim().toIntOrNull()
        if (vagasInt == null || vagasInt <= 0) errs["vagasMaximas"] = "Informe um número de vagas maior que zero."
        val dataApi = DateUtils.inputsToApi(data, hora)
        if (dataApi == null) errs["dataEvento"] = "Use data dd/MM/aaaa e hora HH:mm válidas."
        fieldErrors = errs
        if (errs.isNotEmpty() || vagasInt == null || dataApi == null) return

        viewModelScope.launch {
            saving = true
            val body = EventoRequest(nome.trim(), dataApi, local.trim(), vagasInt)
            when (val r = repo.salvarEvento(editId, body)) {
                is ApiResult.Ok -> onDone()
                is ApiResult.Err -> {
                    error = r.message
                    fieldErrors = r.fieldErrors
                }
            }
            saving = false
        }
    }
}
