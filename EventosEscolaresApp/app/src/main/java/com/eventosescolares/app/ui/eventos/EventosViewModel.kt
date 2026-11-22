package com.eventosescolares.app.ui.eventos

import android.app.Application
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.eventosescolares.app.App
import com.eventosescolares.app.data.ApiResult
import com.eventosescolares.app.data.Evento
import kotlinx.coroutines.launch

class EventosViewModel(application: Application) : AndroidViewModel(application) {

    private val repo = (application as App).repository

    var eventos by mutableStateOf<List<Evento>>(emptyList())
        private set
    var loading by mutableStateOf(true)
        private set
    var error by mutableStateOf<String?>(null)
        private set
    var apenasMeus by mutableStateOf(false)
        private set

    fun load() {
        viewModelScope.launch {
            loading = true
            when (val r = repo.eventos(apenasMeus)) {
                is ApiResult.Ok -> {
                    eventos = r.data.sortedBy { it.dataEvento }
                    error = null
                }
                is ApiResult.Err -> error = r.message
            }
            loading = false
        }
    }

    fun filtrarMeus(value: Boolean) {
        if (value == apenasMeus) return
        apenasMeus = value
        load()
    }
}
