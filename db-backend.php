<?php

interface IDB {
    public function connect(string $host = "", string $username = "", string $password = "", string $database = ""): ?static;
    public function select(string $query): array;
    public function insert(string $table, array $data): bool;
    public function update(string $table, int $id, array $data): bool;
    public function delete(string $table, int $id): bool;
}

class MySQL implements IDB {
    private $connection;

    public function connect(
        string $host = "",
        string $username = "",
        string $password = "",
        string $database = ""
    ): ?static {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            return null;
        }
        return $this;
    }

    public function select(string $query): array {
        $result = $this->connection->query($query);
        if (!$result) {
            return array();
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function insert(string $table, array $data): bool {
        $columns = implode(',', array_keys($data));
        $values = implode(',', array_map(function ($value) {
            return "'" . $this->connection->real_escape_string($value) . "'";
        }, array_values($data)));
        $query = "INSERT INTO $table ($columns) VALUES ($values)";
        return $this->connection->query($query);
    }

    public function update(string $table, int $id, array $data): bool {
        $sets = implode(',', array_map(function ($key, $value) {
            return "$key='" . $this->connection->real_escape_string($value) . "'";
        }, array_keys($data), array_values($data)));
        $query = "UPDATE $table SET $sets WHERE id=$id";
        return $this->connection->query($query);
    }

    public function delete(string $table, int $id): bool {
        $query = "DELETE FROM $table WHERE id=$id";
        return $this->connection->query($query);
    }
}

class Entity {
    private $db;

    public function __construct(IDB $db) {
        $this->db = $db;
    }

    public function getEntities(): array {
        $entities = $this->db->select("SELECT * FROM entities");
        return $entities;
    }
}

class HTMLTable {
    public function generateTable(array $entities): string {
        $html = '<table>';
        $html .= '<thead><tr><th>ID</th><th>Name</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($entities as $entity) {
            $html .= '<tr><td>' . $entity['id'] . '</td><td>' . $entity['name'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
}

$mysql = new MySQL();
$entity = new Entity($mysql);
$htmlTable = new HTMLTable();

$entities = $entity->getEntities();
$table = $htmlTable->