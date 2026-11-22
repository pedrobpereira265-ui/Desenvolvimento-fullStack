package com.eventosescolares.app.ui.eventos

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.compose.LifecycleEventEffect
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.data.Evento
import com.eventosescolares.app.data.Session
import com.eventosescolares.app.ui.common.EmptyState
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.LoadingBox
import com.eventosescolares.app.util.DateUtils

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventosScreen(
    session: Session,
    padding: PaddingValues,
    onOpen: (Int) -> Unit,
    vm: EventosViewModel = viewModel(),
) {
    // Recarrega ao abrir e ao voltar de outras telas (criar/editar/inscrever)
    LifecycleEventEffect(Lifecycle.Event.ON_RESUME) { vm.load() }

    Column(Modifier.padding(padding)) {
        if (session.isGestor) {
            Row(Modifier.padding(horizontal = 16.dp, vertical = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(selected = !vm.apenasMeus, onClick = { vm.filtrarMeus(false) }, label = { Text("Todos") })
                FilterChip(selected = vm.apenasMeus, onClick = { vm.filtrarMeus(true) }, label = { Text("Meus eventos") })
            }
        }
        when {
            vm.loading && vm.eventos.isEmpty() -> LoadingBox()
            vm.error != null && vm.eventos.isEmpty() ->
                ErrorBanner(vm.error!!, Modifier.padding(16.dp), onRetry = { vm.load() })
            vm.eventos.isEmpty() -> EmptyState("Nenhum evento encontrado.")
            else -> LazyColumn(
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                if (vm.error != null) item { ErrorBanner(vm.error!!, onRetry = { vm.load() }) }
                items(vm.eventos, key = { it.id }) { evento ->
                    EventoCard(evento, onClick = { onOpen(evento.id) })
                }
            }
        }
    }
}

@Composable
fun EventoCard(evento: Evento, onClick: () -> Unit) {
    val encerrado = DateUtils.encerrado(evento.dataEvento)
    Card(Modifier.fillMaxWidth().clickable(onClick = onClick)) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            Surface(
                color = MaterialTheme.colorScheme.primaryContainer,
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier.width(60.dp),
            ) {
                Column(Modifier.padding(vertical = 8.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        DateUtils.day(evento.dataEvento),
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onPrimaryContainer,
                    )
                    Text(
                        DateUtils.month(evento.dataEvento),
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onPrimaryContainer,
                        textAlign = TextAlign.Center,
                    )
                }
            }
            Column(Modifier.padding(start = 12.dp), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Text(evento.nomeEvento, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text(DateUtils.shortDate(evento.dataEvento), style = MaterialTheme.typography.bodySmall)
                Text("📍 ${evento.local}", style = MaterialTheme.typography.bodySmall)
                Text(
                    if (encerrado) "Encerrado" else "${evento.vagasMaximas} vagas",
                    style = MaterialTheme.typography.labelMedium,
                    color = if (encerrado) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.secondary,
                )
            }
        }
    }
}
