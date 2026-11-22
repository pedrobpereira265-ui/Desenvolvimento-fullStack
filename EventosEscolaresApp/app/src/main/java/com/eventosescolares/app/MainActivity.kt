package com.eventosescolares.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.getValue
import androidx.compose.runtime.collectAsState
import com.eventosescolares.app.ui.AppRoot
import com.eventosescolares.app.ui.theme.EventosTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val app = application as App
        setContent {
            EventosTheme {
                val session by app.sessionStore.session.collectAsState()
                AppRoot(session)
            }
        }
    }
}
