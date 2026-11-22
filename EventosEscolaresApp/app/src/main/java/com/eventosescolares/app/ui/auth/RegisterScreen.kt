package com.eventosescolares.app.ui.auth

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.eventosescolares.app.data.Roles
import com.eventosescolares.app.ui.common.ErrorBanner
import com.eventosescolares.app.ui.common.PrimaryButton

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RegisterScreen(onBackToLogin: () -> Unit, vm: AuthViewModel = viewModel()) {
    var role by rememberSaveable { mutableStateOf(Roles.ALUNO) }
    var nome by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var ra by rememberSaveable { mutableStateOf("") }
    var senha by rememberSaveable { mutableStateOf("") }

    Column(
        Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
    ) {
        Text("Criar conta", style = MaterialTheme.typography.headlineMedium, color = MaterialTheme.colorScheme.primary)
        Spacer(Modifier.height(16.dp))

        Text("Eu sou:", style = MaterialTheme.typography.labelLarge)
        Row(Modifier.padding(vertical = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf(Roles.ALUNO, Roles.COORDENADOR, Roles.DIRETOR).forEach { r ->
                FilterChip(selected = role == r, onClick = { role = r }, label = { Text(r) })
            }
        }

        vm.error?.let { ErrorBanner(it, Modifier.padding(bottom = 12.dp)) }

        Field("Nome completo", nome, { nome = it }, vm.fieldErrors["nome"])
        Field("E-mail", email, { email = it }, vm.fieldErrors["email"], KeyboardType.Email)
        if (role == Roles.ALUNO) {
            Field("RA", ra, { ra = it }, vm.fieldErrors["ra"])
        }
        Field("Senha (mín. 6 caracteres)", senha, { senha = it }, vm.fieldErrors["senha"], KeyboardType.Password, password = true)

        Spacer(Modifier.height(12.dp))
        PrimaryButton("Cadastrar", vm.loading, onClick = { vm.register(role, nome, email, ra, senha) })
        TextButton(onClick = onBackToLogin, modifier = Modifier.fillMaxWidth()) {
            Text("Já tenho conta")
        }
    }
}

@Composable
private fun Field(
    label: String,
    value: String,
    onChange: (String) -> Unit,
    error: String?,
    keyboard: KeyboardType = KeyboardType.Text,
    password: Boolean = false,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onChange,
        label = { Text(label) },
        singleLine = true,
        isError = error != null,
        supportingText = error?.let { { Text(it) } },
        visualTransformation = if (password) PasswordVisualTransformation() else androidx.compose.ui.text.input.VisualTransformation.None,
        keyboardOptions = KeyboardOptions(keyboardType = keyboard),
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 8.dp),
    )
}
