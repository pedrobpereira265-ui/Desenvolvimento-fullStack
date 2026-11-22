package com.eventosescolares.app.ui.inscricoes

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.compose.LifecycleEventEffect
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.data.Inscricao
import com.eventosescolares.app.ui.common.ConfirmDialog
import com.eventosescolares.app.ui.common.EmptyState
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.LoadingBox
import com.eventosescolares.app.util.DateUtils
import androidx.compose.material3.SnackbarHostState

@Composable
fun MinhasInscricoesScreen(
    padding: PaddingValues,
    snackbar: SnackbarHostState,
    onOpenEvento: (Int) -> Unit,
    vm: MinhasInscricoesViewModel = viewModel(),
) {
    var aCancelar by remember { mutableStateOf<Inscricao?>(null) }

    LifecycleEventEffect(Lifecycle.Event.ON_RESUME) { vm.load() }
    LaunchedEffect(Unit) { vm.messages.collect { snackbar.showSnackbar(it) } }

    Column(Modifier.padding(padding)) {
        when {
            vm.loading && vm.inscricoes.isEmpty() -> LoadingBox()
            vm.error != null && vm.inscricoes.isEmpty() ->
                ErrorBanner(vm.error!!, Modifier.padding(16.dp), onRetry = { vm.load() })
            vm.inscricoes.isEmpty() -> EmptyState("Você ainda não se inscreveu em nenhum evento.")
            else -> LazyColumn(
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                items(vm.inscricoes, key = { it.id }) { insc ->
                    val evento = insc.evento
                    val eventoId = evento?.id ?: insc.eventoId
                    Card(Modifier.fillMaxWidth().clickable { onOpenEvento(eventoId) }) {
                        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Text(
                                evento?.nomeEvento ?: "Evento #$eventoId",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.SemiBold,
                            )
                            if (evento != null) {
                                Text(DateUtils.longDate(evento.dataEvento), style = MaterialTheme.typography.bodySmall)
                                Text("📍 ${evento.local}", style = MaterialTheme.typography.bodySmall)
                            }
                            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                                Text(
                                    if (insc.presencaConfirmada) "✔ Presença confirmada" else "Presença pendente",
                                    style = MaterialTheme.typography.labelLarge,
                                    color = if (insc.presencaConfirmada) MaterialTheme.colorScheme.secondary
                                    else MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.weight(1f),
                                )
                                if (evento == null || !DateUtils.encerrado(evento.dataEvento)) {
                                    TextButton(onClick = { aCancelar = insc }) {
                                        Text("Cancelar", color = MaterialTheme.colorScheme.error)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    aCancelar?.let { insc ->
        ConfirmDialog(
            title = "Cancelar inscrição",
            text = "Deseja cancelar sua inscrição em \"${insc.evento?.nomeEvento ?: "este evento"}\"?",
            confirmLabel = "Cancelar inscrição",
            onConfirm = {
                vm.cancelar(insc)
                aCancelar = null
            },
            onDismiss = { aCancelar = null },
        )
    }
}
