<?php

require_once __DIR__ . '/sql_dialect.php';

class PayrollDbResult
{
    private $rows;
    private $index = 0;
    public $num_rows = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc()
    {
        if ($this->index >= $this->num_rows) {
            return null;
        }
        return $this->rows[$this->index++];
    }

    public function fetch_all($mode = null)
    {
        return $this->rows;
    }
}

function payroll_fetch_all_assoc($result): array
{
    if (!$result) {
        return [];
    }
    if ($result instanceof PayrollDbResult) {
        return $result->fetch_all();
    }
    if (method_exists($result, 'fetch_all')) {
        return $result->fetch_all(defined('MYSQLI_ASSOC') ? MYSQLI_ASSOC : 0) ?: [];
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

class PayrollDbStatement
{
    private $pdo;
    private $sql;
    private $stmt;
    private $types = '';
    private $params = [];
    public $affected_rows = 0;
    public $errno = 0;
    public $error = '';

    public function __construct(PDO $pdo, $sql)
    {
        $this->pdo = $pdo;
        $this->sql = payroll_translate_sql($sql);
        $this->stmt = $pdo->prepare($this->sql);
    }

    public function bind_param($types, &...$params)
    {
        $this->types = $types;
        $this->params = [];
        for ($i = 0, $count = count($params); $i < $count; $i++) {
            $this->params[] = &$params[$i];
        }
        return true;
    }

    public function execute()
    {
        $this->affected_rows = 0;
        $this->errno = 0;
        $this->error = '';

        try {
            $position = 1;
            $type_len = strlen($this->types);
            for ($i = 0; $i < $type_len; $i++) {
                $value = $this->params[$i] ?? null;
                $type = $this->types[$i];
                if ($type === 'i') {
                    $this->stmt->bindValue($position++, (int) $value, PDO::PARAM_INT);
                } elseif ($type === 'd') {
                    if ($value === null || $value === '') {
                        $this->stmt->bindValue($position++, null, PDO::PARAM_NULL);
                    } else {
                        $this->stmt->bindValue($position++, (float) $value);
                    }
                } else {
                    $this->stmt->bindValue($position++, $value, PDO::PARAM_STR);
                }
            }

            $ok = $this->stmt->execute();
            $this->affected_rows = max(0, (int) $this->stmt->rowCount());
            return $ok;
        } catch (PDOException $e) {
            $this->errno = (int) ($e->errorInfo[1] ?? 1);
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function get_result()
    {
        $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return new PayrollDbResult($rows);
    }
}

class PayrollDbConnection
{
    private $pdo;
    public $connect_error = null;
    public $error = '';
    public $errno = 0;

    public function __construct($dsn, $user = null, $pass = null, $options = [])
    {
        try {
            $defaults = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $this->pdo = new PDO($dsn, $user, $pass, $options + $defaults);
        } catch (PDOException $e) {
            $this->connect_error = $e->getMessage();
            $this->error = $e->getMessage();
            $this->errno = (int) ($e->errorInfo[1] ?? 1);
        }
    }

    public function set_charset($charset)
    {
        return true;
    }

    public function real_escape_string($value)
    {
        return str_replace("'", "''", (string) $value);
    }

    public function query($sql)
    {
        $sql = payroll_translate_sql($sql);
        try {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                return false;
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return new PayrollDbResult($rows);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->errno = (int) ($e->errorInfo[1] ?? 1);
            return false;
        }
    }

    public function prepare($sql)
    {
        return new PayrollDbStatement($this->pdo, $sql);
    }
}

function payroll_open_mssql_connection($server, $database, $user = null, $pass = null)
{
    $driver = defined('PAYROLL_MSSQL_ODBC_DRIVER') ? PAYROLL_MSSQL_ODBC_DRIVER : 'ODBC Driver 17 for SQL Server';
    $dsn = 'odbc:Driver={' . $driver . '};Server=' . $server . ';Database=' . $database . ';TrustServerCertificate=Yes';
    if ($user === null || $user === '') {
        $dsn .= ';Trusted_Connection=Yes';
        return new PayrollDbConnection($dsn);
    }
    return new PayrollDbConnection($dsn, $user, $pass);
}

function payroll_open_mysql_connection($host, $database, $user, $pass)
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli($host, $user, $pass, $database);
    if ($conn->connect_errno === 1049) {
        $bootstrap = new mysqli($host, $user, $pass);
        if ($bootstrap->connect_error) {
            return $conn;
        }
        $safe_db = str_replace('`', '``', $database);
        $bootstrap->query("CREATE DATABASE IF NOT EXISTS `{$safe_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $bootstrap->close();
        $conn = new mysqli($host, $user, $pass, $database);
    }
    return $conn;
}
