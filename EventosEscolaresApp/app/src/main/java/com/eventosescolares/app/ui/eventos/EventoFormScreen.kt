package com.eventosescolares.app.ui.eventos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.LoadingBox
import com.eventosescolares.app.ui.common.PrimaryButton

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun EventoFormScreen(onBack: () -> Unit, vm: EventoFormViewModel = viewModel()) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (vm.isEdit) "Editar evento" else "Novo evento") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Voltar") }
                },
            )
        },
    ) { padding ->
        if (vm.loading) {
            LoadingBox(Modifier.padding(padding))
            return@Scaffold
        }
        Column(
            Modifier
                .padding(padding)
                .fillMaxSize()
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            vm.error?.let { ErrorBanner(it) }

            OutlinedTextField(
                value = vm.nome,
                onValueChange = { vm.nome = it },
                label = { Text("Nome do evento") },
                isError = vm.fieldErrors.containsKey("nomeEvento"),
                supportingText = vm.fieldErrors["nomeEvento"]?.let { { Text(it) } },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = vm.data,
                    onValueChange = { vm.data = it },
                    label = { Text("Data (dd/mm/aaaa)") },
                    isError = vm.fieldErrors.containsKey("dataEvento"),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.weight(1.4f),
                )
                OutlinedTextField(
                    value = vm.hora,
                    onValueChange = { vm.hora = it },
                    label = { Text("Hora (HH:mm)") },
                    isError = vm.fieldErrors.containsKey("dataEvento"),
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                )
            }
            vm.fieldErrors["dataEvento"]?.let {
                Text(it, color = androidx.compose.material3.MaterialTheme.colorScheme.error, style = androidx.compose.material3.MaterialTheme.typography.bodySmall)
            }
            OutlinedTextField(
                value = vm.local,
                onValueChange = { vm.local = it },
                label = { Text("Local") },
                isError = vm.fieldErrors.containsKey("local"),
                supportingText = vm.fieldErrors["local"]?.let { { Text(it) } },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            OutlinedTextField(
                value = vm.vagas,
                onValueChange = { vm.vagas = it.filter(Char::isDigit) },
                label = { Text("Vagas máximas") },
                isError = vm.fieldErrors.containsKey("vagasMaximas"),
                supportingText = vm.fieldErrors["vagasMaximas"]?.let { { Text(it) } },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                modifier = Modifier.fillMaxWidth(),
            )
            Text("O horário é o de Brasília (America/Sao_Paulo).", style = androidx.compose.material3.MaterialTheme.typography.bodySmall)

            PrimaryButton(
                text = if (vm.isEdit) "Salvar alterações" else "Criar evento",
                loading = vm.saving,
                onClick = { vm.salvar(onDone = onBack) },
            )
        }
    }
}
