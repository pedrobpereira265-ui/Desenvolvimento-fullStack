package com.eventosescolares.app.ui.eventos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.compose.LifecycleEventEffect
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.data.Session
import com.eventosescolares.app.ui.common.ConfirmDialog
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.InfoRow
import com.eventosescolares.app.ui.common.LoadingBox
import com.eventosescolares.app.ui.common.PrimaryButton
import com.eventosescolares.app.util.DateUtils

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventoDetailScreen(
    session: Session,
    onBack: () -> Unit,
    onEdit: (Int) -> Unit,
    onInscritos: (Int) -> Unit,
    vm: EventoDetailViewModel = viewModel(),
) {
    val snackbar = remember { SnackbarHostState() }
    var confirmDelete by remember { mutableStateOf(false) }
    var confirmCancel by remember { mutableStateOf(false) }

    LifecycleEventEffect(Lifecycle.Event.ON_RESUME) { vm.load() }
    LaunchedEffect(Unit) { vm.messages.collect { snackbar.showSnackbar(it) } }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Detalhes do evento") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Voltar") }
                },
            )
        },
        snackbarHost = { SnackbarHost(snackbar) },
    ) { padding ->
        val evento = vm.evento
        when {
            vm.loading && evento == null -> LoadingBox(Modifier.padding(padding))
            evento == null -> ErrorBanner(vm.error ?: "Evento não encontrado.", Modifier.padding(padding).padding(16.dp), onRetry = { vm.load() })
            else -> Column(
                Modifier
                    .padding(padding)
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Text(evento.nomeEvento, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)

                Card(Modifier.fillMaxWidth()) {
                    Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        InfoRow("Data", DateUtils.longDate(evento.dataEvento))
                        InfoRow("Local", evento.local)
                        InfoRow("Vagas", evento.vagasMaximas.toString())
                        InfoRow("Organizador", evento.organizador)
                    }
                }

                val encerrado = DateUtils.encerrado(evento.dataEvento)
                if (encerrado) {
                    Text("Este evento já foi encerrado.", color = MaterialTheme.colorScheme.error)
                }

                if (session.isAluno) {
                    val insc = vm.minhaInscricao
                    when {
                        insc != null -> {
                            Text(
                                if (insc.presencaConfirmada) "✔ Você está inscrito e sua presença foi confirmada."
                                else "✔ Você está inscrito neste evento.",
                                color = MaterialTheme.colorScheme.secondary,
                            )
                            if (!encerrado) {
                                OutlinedButton(
                                    onClick = { confirmCancel = true },
                                    enabled = !vm.busy,
                                    modifier = Modifier.fillMaxWidth(),
                                ) { Text("Cancelar inscrição") }
                            }
                        }
                        !encerrado -> PrimaryButton("Inscrever-se", vm.busy, onClick = { vm.inscrever() })
                    }
                }

                if (session.podeGerenciar(evento)) {
                    Button(onClick = { onInscritos(evento.id) }, modifier = Modifier.fillMaxWidth()) {
                        Text("Ver inscritos e presença")
                    }
                    OutlinedButton(onClick = { onEdit(evento.id) }, modifier = Modifier.fillMaxWidth()) {
                        Text("Editar evento")
                    }
                    OutlinedButton(
                        onClick = { confirmDelete = true },
                        enabled = !vm.busy,
                        modifier = Modifier.fillMaxWidth(),
                    ) { Text("Excluir evento", color = MaterialTheme.colorScheme.error) }
                }
            }
        }
    }

    if (confirmDelete) {
        ConfirmDialog(
            title = "Excluir evento",
            text = "Tem certeza? Esta ação não pode ser desfeita.",
            confirmLabel = "Excluir",
            onConfirm = {
                confirmDelete = false
                vm.excluir(onDeleted = onBack)
            },
            onDismiss = { confirmDelete = false },
        )
    }
    if (confirmCancel) {
        ConfirmDialog(
            title = "Cancelar inscrição",
            text = "Deseja cancelar sua inscrição neste evento?",
            confirmLabel = "Cancelar inscrição",
            onConfirm = {
                confirmCancel = false
                vm.cancelarInscricao()
            },
            onDismiss = { confirmCancel = false },
        )
    }
}
