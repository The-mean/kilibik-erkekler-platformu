<?php
require_once __DIR__ . '/../includes/security.php';

define('DB_FILE', __DIR__ . '/../database/sozluk.db');

class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        try {
            // Veritabanı dizininin varlığını kontrol et
            $dbDir = dirname(DB_FILE);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }

            // Veritabanı bağlantısını oluştur
            $this->db = new PDO('sqlite:' . DB_FILE);
            
            // Hata modunu ayarla
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // SQLite güvenlik ayarları
            $this->db->exec('PRAGMA foreign_keys = ON');
            $this->db->exec('PRAGMA journal_mode = WAL');
            $this->db->exec('PRAGMA synchronous = NORMAL');

            // Veritabanı dosyasının yazılabilir olduğunu kontrol et
            if (!is_writable(DB_FILE) && file_exists(DB_FILE)) {
                throw new Exception('Veritabanı dosyası yazılabilir değil: ' . DB_FILE);
            }

        } catch (PDOException $e) {
            error_log('PDO Hatası: ' . $e->getMessage());
            die('Veritabanı bağlantı hatası: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('Genel Hata: ' . $e->getMessage());
            die('Veritabanı hatası: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    public function prepare($sql) {
        return $this->db->prepare($sql);
    }

    /**
     * Güvenli sorgu çalıştırma
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception('Sorgu hazırlanamadı');
            }
            
            // Parametreleri bind et
            foreach ($params as $param => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($param, Security::sanitize($value), $type);
            }
            
            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            error_log('Sorgu hatası: ' . $e->getMessage() . ' - SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Tek satır döndürür
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tüm satırları döndürür
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * INSERT sorgusu çalıştırır
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $params = array_map(function($field) { return ':' . $field; }, $fields);
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $fields),
            implode(', ', $params)
        );
        
        $bindParams = array_combine($params, array_values($data));
        
        $this->query($sql, $bindParams);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE sorgusu çalıştırır
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) { 
            return $field . ' = :' . $field; 
        }, array_keys($data));
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $fields),
            $where
        );
        
        $bindParams = array_combine(
            array_map(function($field) { return ':' . $field; }, array_keys($data)),
            array_values($data)
        );
        
        $bindParams = array_merge($bindParams, $whereParams);
        
        return $this->query($sql, $bindParams);
    }

    /**
     * DELETE sorgusu çalıştırır
     */
    public function delete($table, $where, $params = []) {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        return $this->query($sql, $params);
    }

    /**
     * Veritabanı bağlantısını kapatır
     */
    public function __destruct() {
        $this->db = null;
    }
} 