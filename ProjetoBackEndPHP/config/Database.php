<?php

namespace App\Config;

use App\Exceptions\DatabaseException;
use PDO;
use PDOException;

/**
 * Configuração de conexão com o banco de dados MySQL via PDO.
 * Padrão pré-configurado para ambiente XAMPP local.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Retorna a instância única do PDO (Singleton).
     *
     * @throws DatabaseException se a conexão falhar (tratada pelo Router).
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_NAME') ?: 'gestao_eventos_escolares';
            $user = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                // Registra o detalhe técnico no log do servidor, não na resposta HTTP.
                error_log('[Database] Falha na conexão MySQL: ' . $e->getMessage());

                throw new DatabaseException(
                    'Falha ao conectar com o banco de dados MySQL. Verifique se o MySQL está rodando e se a base "' . $dbName . '" foi criada.',
                    $e
                );
            }
        }

        return self::$connection;
    }
}
