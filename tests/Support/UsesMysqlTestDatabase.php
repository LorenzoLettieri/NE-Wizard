<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait UsesMysqlTestDatabase
{
    protected function beforeRefreshingDatabase(): void
    {
        if (extension_loaded('pdo_sqlite')) {
            return;
        }

        $mysql = config('database.connections.mysql');
        $database = env('MYSQL_TEST_DATABASE', 'NEwizard_test');
        $connection = 'mysql_media_test';

        $this->ensureMysqlDatabaseExists(
            host: (string) $mysql['host'],
            port: (string) $mysql['port'],
            username: (string) $mysql['username'],
            password: (string) $mysql['password'],
            database: $database,
            charset: (string) ($mysql['charset'] ?? 'utf8mb4'),
            collation: (string) ($mysql['collation'] ?? 'utf8mb4_unicode_ci'),
        );

        config([
            'database.default' => $connection,
            "database.connections.{$connection}" => array_merge($mysql, [
                'database' => $database,
            ]),
        ]);

        DB::purge($connection);
        DB::setDefaultConnection($connection);
    }

    private function ensureMysqlDatabaseExists(
        string $host,
        string $port,
        string $username,
        string $password,
        string $database,
        string $charset,
        string $collation,
    ): void {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $host, $port, $charset);
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $escapedDatabase = str_replace('`', '``', $database);

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
            $escapedDatabase,
            $charset,
            $collation
        ));
    }
}
