package com.eventosescolares.app.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.eventosescolares.app.App
import com.eventosescolares.app.data.Session
import com.eventosescolares.app.ui.auth.LoginScreen
import com.eventosescolares.app.ui.auth.RegisterScreen
import com.eventosescolares.app.ui.eventos.EventoDetailScreen
import com.eventosescolares.app.ui.eventos.EventoFormScreen
import com.eventosescolares.app.ui.eventos.EventosScreen
import com.eventosescolares.app.ui.inscricoes.InscritosScreen
import com.eventosescolares.app.ui.inscricoes.MinhasInscricoesScreen

object Routes {
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val EVENTOS = "eventos"
    const val MINHAS = "minhas"
    const val DETALHE = "evento/{id}"
    const val FORM = "evento-form?id={id}"
    const val INSCRITOS = "inscritos/{id}"

    fun detalhe(id: Int) = "evento/$id"
    fun form(id: Int?) = "evento-form?id=${id ?: -1}"
    fun inscritos(id: Int) = "inscritos/$id"
}

/**
 * Sem sessão -> fluxo de login/cadastro. Com sessão -> app principal.
 * Como a sessão é um StateFlow, o logout (ou um 401 da API) volta ao login automaticamente.
 */
@Composable
fun AppRoot(session: Session?) {
    if (session == null) AuthNav() else MainNav(session)
}

@Composable
private fun AuthNav() {
    val nav = rememberNavController()
    // Login/cadastro não usam Scaffold: respeitam barras do sistema e teclado manualmente.
    Surface(
        modifier = Modifier.fillMaxSize().safeDrawingPadding(),
        color = MaterialTheme.colorScheme.background,
    ) {
        NavHost(nav, startDestination = Routes.LOGIN) {
            composable(Routes.LOGIN) {
                LoginScreen(onRegister = { nav.navigate(Routes.REGISTER) })
            }
            composable(Routes.REGISTER) {
                RegisterScreen(onBackToLogin = { nav.popBackStack() })
            }
        }
    }
}

@Composable
private fun MainNav(session: Session) {
    val nav = rememberNavController()
    val app = LocalContext.current.applicationContext as App

    fun navigateTop(route: String) = nav.navigate(route) {
        popUpTo(Routes.EVENTOS)
        launchSingleTop = true
    }

    NavHost(nav, startDestination = Routes.EVENTOS) {

        composable(Routes.EVENTOS) {
            val snackbar = remember { SnackbarHostState() }
            HomeScaffold(
                session = session,
                current = Routes.EVENTOS,
                title = "Eventos",
                snackbar = snackbar,
                onNavigate = ::navigateTop,
                onLogout = { app.repository.logout() },
                floatingActionButton = {
                    if (session.isGestor) {
                        FloatingActionButton(onClick = { nav.navigate(Routes.form(null)) }) {
                            Icon(Icons.Filled.Add, contentDescription = "Novo evento")
                        }
                    }
                },
            ) { padding ->
                EventosScreen(session, padding, onOpen = { nav.navigate(Routes.detalhe(it)) })
            }
        }

        composable(Routes.MINHAS) {
            val snackbar = remember { SnackbarHostState() }
            HomeScaffold(
                session = session,
                current = Routes.MINHAS,
                title = "Minhas inscrições",
                snackbar = snackbar,
                onNavigate = ::navigateTop,
                onLogout = { app.repository.logout() },
            ) { padding ->
                MinhasInscricoesScreen(padding, snackbar, onOpenEvento = { nav.navigate(Routes.detalhe(it)) })
            }
        }

        composable(Routes.DETALHE, arguments = listOf(navArgument("id") { type = NavType.IntType })) {
            EventoDetailScreen(
                session = session,
                onBack = { nav.popBackStack() },
                onEdit = { nav.navigate(Routes.form(it)) },
                onInscritos = { nav.navigate(Routes.inscritos(it)) },
            )
        }

        composable(
            Routes.FORM,
            arguments = listOf(navArgument("id") { type = NavType.IntType; defaultValue = -1 }),
        ) {
            EventoFormScreen(onBack = { nav.popBackStack() })
        }

        composable(Routes.INSCRITOS, arguments = listOf(navArgument("id") { type = NavType.IntType })) {
            InscritosScreen(onBack = { nav.popBackStack() })
        }
    }
}
