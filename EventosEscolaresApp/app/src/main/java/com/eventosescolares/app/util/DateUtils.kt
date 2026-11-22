package com.eventosescolares.app.util

import java.time.Instant
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.LocalTime
import java.time.ZoneId
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.time.format.ResolverStyle
import java.util.Locale

/**
 * A API trabalha em UTC (ex.: "2026-10-15T19:00:00Z"); o usuário vê e digita em
 * America/Sao_Paulo (mesmo APP_TZ do front PHP).
 */
object DateUtils {
    private val zone: ZoneId = ZoneId.of("America/Sao_Paulo")
    private val ptBR = Locale("pt", "BR")

    private val longFmt = DateTimeFormatter.ofPattern("EEEE, dd/MM/yyyy 'às' HH:mm", ptBR)
    private val shortFmt = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm", ptBR)
    private val dayFmt = DateTimeFormatter.ofPattern("dd", ptBR)
    private val monthFmt = DateTimeFormatter.ofPattern("MMM", ptBR)
    private val inDate = DateTimeFormatter.ofPattern("dd/MM/uuuu").withResolverStyle(ResolverStyle.STRICT)
    private val inTime = DateTimeFormatter.ofPattern("HH:mm").withResolverStyle(ResolverStyle.STRICT)
    private val apiFmt = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss")

    fun parse(iso: String): Instant = try {
        Instant.parse(iso)
    } catch (e: Exception) {
        // Tolera "2026-10-15 19:00:00" (sem "T"/"Z"), tratado como UTC
        LocalDateTime.parse(iso.trim().replace(' ', 'T')).toInstant(ZoneOffset.UTC)
    }

    private fun local(iso: String) = parse(iso).atZone(zone)

    fun longDate(iso: String): String = runCatching { local(iso).format(longFmt) }.getOrDefault(iso)
    fun shortDate(iso: String): String = runCatching { local(iso).format(shortFmt) }.getOrDefault(iso)
    fun day(iso: String): String = runCatching { local(iso).format(dayFmt) }.getOrDefault("--")
    fun month(iso: String): String =
        runCatching { local(iso).format(monthFmt).uppercase(ptBR).trimEnd('.') }.getOrDefault("---")

    fun encerrado(iso: String): Boolean = runCatching { parse(iso).isBefore(Instant.now()) }.getOrDefault(false)

    /** Para preencher o formulário de edição: (dd/MM/yyyy, HH:mm) no fuso local. */
    fun toInputs(iso: String): Pair<String, String> = runCatching {
        val d = local(iso)
        d.format(DateTimeFormatter.ofPattern("dd/MM/yyyy")) to d.format(DateTimeFormatter.ofPattern("HH:mm"))
    }.getOrDefault("" to "")

    /** Converte o que o usuário digitou (horário de São Paulo) para o formato aceito pela API (UTC). */
    fun inputsToApi(date: String, time: String): String? = runCatching {
        val dt = LocalDateTime.of(LocalDate.parse(date.trim(), inDate), LocalTime.parse(time.trim(), inTime))
        dt.atZone(zone).withZoneSameInstant(ZoneOffset.UTC).format(apiFmt)
    }.getOrNull()
}
