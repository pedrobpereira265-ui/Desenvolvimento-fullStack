package com.eventosescolares.app.ui.inscricoes

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.compose.LifecycleEventEffect
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.ui.common.EmptyState
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.LoadingBox

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InscritosScreen(onBack: () -> Unit, vm: InscritosViewModel = viewModel()) {
    val snackbar = remember { SnackbarHostState() }

    LifecycleEventEffect(Lifecycle.Event.ON_RESUME) { vm.load() }
    LaunchedEffect(Unit) { vm.messages.collect { snackbar.showSnackbar(it) } }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Inscritos")
                        if (vm.nomeEvento.isNotEmpty()) {
                            Text(vm.nomeEvento, style = MaterialTheme.typography.labelMedium)
                        }
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Voltar") }
                },
            )
        },
        snackbarHost = { SnackbarHost(snackbar) },
    ) { padding ->
        Column(Modifier.padding(padding)) {
            when {
                vm.loading && vm.inscritos.isEmpty() -> LoadingBox()
                vm.error != null && vm.inscritos.isEmpty() ->
                    ErrorBanner(vm.error!!, Modifier.padding(16.dp), onRetry = { vm.load() })
                vm.inscritos.isEmpty() -> EmptyState("Nenhum aluno inscrito neste evento ainda.")
                else -> {
                    Text(
                        "${vm.inscritos.size} inscritos • ${vm.confirmados} com presença confirmada",
                        style = MaterialTheme.typography.labelLarge,
                        modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                    )
                    LazyColumn(
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp),
                    ) {
                        items(vm.inscritos, key = { it.id }) { insc ->
                            Card(Modifier.fillMaxWidth()) {
                                Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                                    Column(Modifier.weight(1f)) {
                                        Text(
                                            insc.aluno?.nome ?: "Aluno #${insc.alunoId}",
                                            style = MaterialTheme.typography.titleMedium,
                                            fontWeight = FontWeight.SemiBold,
                                        )
                                        insc.aluno?.ra?.takeIf { it.isNotBlank() }?.let {
                                            Text("RA: $it", style = MaterialTheme.typography.bodySmall)
                                        }
                                        insc.aluno?.email?.takeIf { it.isNotBlank() }?.let {
                                            Text(it, style = MaterialTheme.typography.bodySmall)
                                        }
                                    }
                                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                        Switch(
                                            checked = insc.presencaConfirmada,
                                            onCheckedChange = { vm.setPresenca(insc, it) },
                                        )
                                        Text("Presente", style = MaterialTheme.typography.labelSmall)
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
