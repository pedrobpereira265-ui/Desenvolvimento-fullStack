package com.eventosescolares.app.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.automirrored.filled.List
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import com.eventosescolares.app.data.Session
import com.eventosescolares.app.ui.common.ConfirmDialog

/** Estrutura das telas "principais": barra superior com usuário/sair e, para alunos, barra inferior. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScaffold(
    session: Session,
    current: String,
    title: String,
    snackbar: SnackbarHostState,
    onNavigate: (String) -> Unit,
    onLogout: () -> Unit,
    floatingActionButton: @Composable () -> Unit = {},
    content: @Composable (PaddingValues) -> Unit,
) {
    var confirmLogout by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(title)
                        Text("${session.nome} • ${session.role}", style = MaterialTheme.typography.labelSmall)
                    }
                },
                actions = {
                    IconButton(onClick = { confirmLogout = true }) {
                        Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = "Sair")
                    }
                },
            )
        },
        bottomBar = {
            if (session.isAluno) {
                NavigationBar {
                    NavigationBarItem(
                        selected = current == Routes.EVENTOS,
                        onClick = { onNavigate(Routes.EVENTOS) },
                        icon = { Icon(Icons.Filled.DateRange, contentDescription = null) },
                        label = { Text("Eventos") },
                    )
                    NavigationBarItem(
                        selected = current == Routes.MINHAS,
                        onClick = { onNavigate(Routes.MINHAS) },
                        icon = { Icon(Icons.AutoMirrored.Filled.List, contentDescription = null) },
                        label = { Text("Minhas inscrições") },
                    )
                }
            }
        },
        snackbarHost = { SnackbarHost(snackbar) },
        floatingActionButton = floatingActionButton,
        content = content,
    )

    if (confirmLogout) {
        ConfirmDialog(
            title = "Sair",
            text = "Deseja encerrar a sessão?",
            confirmLabel = "Sair",
            onConfirm = {
                confirmLogout = false
                onLogout()
            },
            onDismiss = { confirmLogout = false },
        )
    }
}
