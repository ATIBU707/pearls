<?php
/**
 * Base Model Class
 * Online Hostel Management System
 * 
 * All model classes should extend this base class
 */

class Model {
    protected $conn;
    protected $table;
    protected $primaryKey;
    
    public function __construct($table, $primaryKey = 'id') {
        global $conn;
        $this->conn = $conn;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }
    
    /**
     * Get all records
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table}";
        return $this->query($sql);
    }
    
    /**
     * Get record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Insert record
     */
    public function insert($data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        // Build types string
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }
        
        $values = array_values($data);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute() ? $this->conn->insert_id : false;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $sets) . " WHERE {$this->primaryKey} = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        // Build types string
        $types = '';
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }
        $types .= 'i'; // for the ID
        
        $values = array_values($data);
        $values[] = $id;
        
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    
    /**
     * Run query and get all results
     */
    protected function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Run query and get single result
     */
    protected function queryOne($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return null;
        }
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get parameter types for binding
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        return $types;
    }
    
    /**
     * Check if record exists
     */
    public function exists($sql, $params = []) {
        $result = $this->queryOne($sql, $params);
        return $result !== null;
    }
    
    /**
     * Count records
     */
    public function count($sql = null, $params = []) {
        if ($sql === null) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        }
        
        $result = $this->queryOne($sql, $params);
        return $result['count'] ?? 0;
    }
}

?>
