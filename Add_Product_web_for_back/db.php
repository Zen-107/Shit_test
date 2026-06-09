<?php
// db.php - Supabase Configuration for Admin Panel

// Supabase Configuration
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://vsyshokvaefyhjwbuufo.supabase.co');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: 'sb_publishable_PTTHyU8eaSUBI8xMPBTjJg_Cpe11gye');

// Import Supabase functions from config.php
require_once __DIR__ . '/../H3/api/config.php';

// ============================================
// PDO-like Wrapper for Backward Compatibility
// ============================================
// สร้าง class ที่เลียนแบบ PDO เพื่อให้โค้ดเดิมยังทำงานได้

class SupabasePDO {
    private $baseUrl;
    private $apiKey;
    
    public function __construct() {
        $this->baseUrl = SUPABASE_URL . '/rest/v1/';
        $this->apiKey = SUPABASE_KEY;
    }
    
    public function prepare($sql) {
        return new SupabaseStatement($this->baseUrl, $this->apiKey, $sql);
    }
    
    public function setAttribute($attr, $value) {
        // ไม่จำเป็นต้องใช้สำหรับ Supabase
        return true;
    }
    
    public function query($sql) {
        // Parse SQL and convert to Supabase API call
        // This is a simplified version
        return new SupabaseResult([]);
    }
}

class SupabaseStatement {
    private $baseUrl;
    private $apiKey;
    private $sql;
    private $params = [];
    
    public function __construct($baseUrl, $apiKey, $sql) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->sql = $sql;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        
        // Parse SQL to determine operation type
        $sql = trim($this->sql);
        
        if (stripos($sql, 'SELECT') === 0) {
            return $this->executeSelect();
        } elseif (stripos($sql, 'INSERT') === 0) {
            return $this->executeInsert();
        } elseif (stripos($sql, 'UPDATE') === 0) {
            return $this->executeUpdate();
        } elseif (stripos($sql, 'DELETE') === 0) {
            return $this->executeDelete();
        }
        
        return false;
    }
    
    private function executeSelect() {
        // Parse SELECT statement
        preg_match('/FROM\s+`?(\w+)`?/i', $this->sql, $matches);
        $table = $matches[1] ?? '';
        
        if (empty($table)) return false;
        
        // Build URL with conditions
        $url = $this->baseUrl . $table . '?select=*';
        
        // Add WHERE conditions if present
        if (!empty($this->params)) {
            preg_match_all('/WHERE\s+.*?=\s*\?/i', $this->sql, $whereMatches);
            preg_match_all('/`?(\w+)`?\s*=\s*\?/i', $this->sql, $columnMatches);
            
            if (!empty($columnMatches[1])) {
                foreach ($columnMatches[1] as $index => $column) {
                    if (isset($this->params[$index])) {
                        $url .= '&' . $column . '=eq.' . urlencode($this->params[$index]);
                    }
                }
            }
        }
        
        $result = $this->makeRequest($url, 'GET');
        return new SupabaseResult($result ?: []);
    }
    
    private function executeInsert() {
        // Parse INSERT statement
        preg_match('/INTO\s+`?(\w+)`?/i', $this->sql, $matches);
        $table = $matches[1] ?? '';
        
        if (empty($table)) return false;
        
        // Extract column names
        preg_match('/\((.*?)\)\s*VALUES/i', $this->sql, $colMatches);
        $columns = array_map('trim', explode(',', str_replace('`', '', $colMatches[1] ?? '')));
        
        // Build data array
        $data = [];
        foreach ($columns as $index => $column) {
            if (isset($this->params[$index])) {
                $data[$column] = $this->params[$index];
            }
        }
        
        $url = $this->baseUrl . $table;
        $result = $this->makeRequest($url, 'POST', $data);
        
        return $result !== false;
    }
    
    private function executeUpdate() {
        // Parse UPDATE statement
        preg_match('/UPDATE\s+`?(\w+)`?\s+SET/i', $this->sql, $matches);
        $table = $matches[1] ?? '';
        
        if (empty($table)) return false;
        
        // Extract SET columns
        preg_match('/SET\s+(.*?)\s+WHERE/i', $this->sql, $setMatches);
        preg_match_all('/`?(\w+)`?\s*=\s*\?/i', $setMatches[1] ?? '', $setColumnMatches);
        
        // Extract WHERE columns
        preg_match('/WHERE\s+(.*)/i', $this->sql, $whereMatches);
        preg_match_all('/`?(\w+)`?\s*=\s*\?/i', $whereMatches[1] ?? '', $whereColumnMatches);
        
        // Build data and conditions
        $data = [];
        $paramIndex = 0;
        
        foreach ($setColumnMatches[1] as $column) {
            if (isset($this->params[$paramIndex])) {
                $data[$column] = $this->params[$paramIndex];
            }
            $paramIndex++;
        }
        
        $url = $this->baseUrl . $table;
        foreach ($whereColumnMatches[1] as $column) {
            if (isset($this->params[$paramIndex])) {
                $url .= '?' . $column . '=eq.' . urlencode($this->params[$paramIndex]);
            }
            $paramIndex++;
        }
        
        $result = $this->makeRequest($url, 'PATCH', $data);
        return $result !== false;
    }
    
    private function executeDelete() {
        // Parse DELETE statement
        preg_match('/FROM\s+`?(\w+)`?/i', $this->sql, $matches);
        $table = $matches[1] ?? '';
        
        if (empty($table)) return false;
        
        // Extract WHERE conditions
        preg_match('/WHERE\s+(.*)/i', $this->sql, $whereMatches);
        preg_match_all('/`?(\w+)`?\s*=\s*\?/i', $whereMatches[1] ?? '', $whereColumnMatches);
        
        $url = $this->baseUrl . $table;
        foreach ($whereColumnMatches[1] as $index => $column) {
            if (isset($this->params[$index])) {
                $url .= ($index === 0 ? '?' : '&') . $column . '=eq.' . urlencode($this->params[$index]);
            }
        }
        
        $result = $this->makeRequest($url, 'DELETE');
        return $result !== false;
    }
    
    private function makeRequest($url, $method, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        error_log("Supabase API Error: HTTP $httpCode - $response");
        return false;
    }
    
    public function fetchAll() {
        // This will be called on the result
        return [];
    }
}

class SupabaseResult {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function fetch($mode = PDO::FETCH_ASSOC) {
        return !empty($this->data) ? array_shift($this->data) : false;
    }
    
    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        return $this->data;
    }
    
    public function rowCount() {
        return count($this->data);
    }
}

// ============================================
// Initialize PDO-like connection
// ============================================
$pdo = new SupabasePDO();

// Constants for backward compatibility
define('PDO::ATTR_ERRMODE', 'errmode');
define('PDO::ERRMODE_EXCEPTION', 'exception');
define('PDO::FETCH_ASSOC', 'assoc');
?>