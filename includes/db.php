<?php
/**
 * Mie Time - Database Connection
 * Menggunakan PDO untuk keamanan dan fleksibilitas
 */

if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Database connection error. Please try again later.");
            }
        }
    }
    
    /**
     * Singleton pattern - hanya satu koneksi database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function untuk mendapatkan koneksi database
 */
function get_db() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute query dengan prepared statement
 */
function db_query($sql, $params = []) {
    try {
        $db = get_db();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Query error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Fetch single row
 */
function db_fetch($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

/**
 * Fetch all rows
 */
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Insert data dan return last insert ID
 */
function db_insert($table, $data) {
    $keys = array_keys($data);
    $fields = implode(', ', $keys);
    $placeholders = ':' . implode(', :', $keys);
    
    $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
    
    try {
        $db = get_db();
        $stmt = $db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $db->lastInsertId();
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Insert error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Update data
 */
function db_update($table, $data, $where, $where_params = []) {
    $set = [];
    foreach (array_keys($data) as $key) {
        $set[] = "$key = :$key";
    }
    $set_string = implode(', ', $set);
    
    $sql = "UPDATE $table SET $set_string WHERE $where";
    
    try {
        $db = get_db();
        $stmt = $db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        foreach ($where_params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Update error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Delete data
 */
function db_delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    
    try {
        $db = get_db();
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Delete error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Count rows
 */
function db_count($table, $where = '1=1', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = db_fetch($sql, $params);
    return $result ? (int)$result['total'] : 0;
}

/**
 * Check if record exists
 */
function db_exists($table, $where, $params = []) {
    return db_count($table, $where, $params) > 0;
}

/**
 * Begin transaction
 */
function db_begin_transaction() {
    return get_db()->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit() {
    return get_db()->commit();
}

/**
 * Rollback transaction
 */
function db_rollback() {
    return get_db()->rollBack();
}