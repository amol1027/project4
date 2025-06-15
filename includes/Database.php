<?php
/**
 * Database connection and operations class
 */
class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $conn;
    private static $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->dbname = DB_NAME;
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Log error and display generic message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a query with parameters
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw new Exception("Database query failed.");
        }
    }
    
    /**
     * Get a single row from the database
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false if no results
     */
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Get multiple rows from the database
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return array Array of rows
     */
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert data into a table
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $values = array_values($data);
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $values);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Update data in a table
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where Where clause
     * @param array $params Parameters for where clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $params = []) {
        $set = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $set = implode(", ", $set);
        $values = array_merge($values, $params);
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $stmt = $this->query($sql, $values);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data from a table
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Parameters for where clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollBack() {
        $this->conn->rollBack();
    }
}