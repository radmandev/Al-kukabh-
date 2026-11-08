<?php
// Thin PDO layer (MySQL). Every query is a prepared statement.

function db(): PDO
{
    static $pdo;
    if ($pdo) return $pdo;
    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) ($c['port'] ?? 3306), $c['name']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // keep MySQL's CURRENT_TIMESTAMP in the same zone PHP writes dates in
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function val(string $sql, array $params = []): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** "?, ?, ?" for an IN (...) list; never empty (an empty list matches nothing). */
function in_list(array $ids): string
{
    return $ids ? implode(',', array_fill(0, count($ids), '?')) : 'NULL';
}

function insert(string $table, array $data): void
{
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . '`) VALUES (' . in_list($cols) . ')';
    q($sql, array_values(array_map('db_value', $data)));
}

function update(string $table, array $data, string $where, array $params = []): int
{
    $set = implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($data)));
    return q("UPDATE `$table` SET $set WHERE $where", [...array_values(array_map('db_value', $data)), ...$params])->rowCount();
}

function db_value(mixed $v): mixed
{
    return is_bool($v) ? (int) $v : $v;
}

/** LIKE '%term%' with wildcards in the term escaped. */
function like(string $term): string
{
    return '%' . addcslashes($term, '%_\\') . '%';
}

function tx(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
