<?php
class Logger {
    private static $instance = null;
    private $logPath;
    private $errorLogPath;
    private $accessLogPath;
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../logs';
        $this->errorLogPath = $this->logPath . '/error.log';
        $this->accessLogPath = $this->logPath . '/access.log';
        
        // Log dizinini oluştur
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Hata loglar
     */
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * Uyarı loglar
     */
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    /**
     * Bilgi loglar
     */
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * Debug bilgisi loglar
     */
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    /**
     * Erişim loglar
     */
    public function access($message, $context = []) {
        $logEntry = $this->formatLogEntry('ACCESS', $message, $context);
        file_put_contents($this->accessLogPath, $logEntry . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Log girdisi oluşturur ve kaydeder
     */
    private function log($level, $message, $context = []) {
        $logEntry = $this->formatLogEntry($level, $message, $context);
        file_put_contents($this->errorLogPath, $logEntry . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Log girdisi formatlar
     */
    private function formatLogEntry($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = Security::getIpAddress();
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Context bilgilerini JSON formatına dönüştür
        $contextJson = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '{}';
        
        return sprintf(
            '[%s] [%s] [IP: %s] [User: %s] [URI: %s] %s %s',
            $timestamp,
            $level,
            $ip,
            $userId,
            $requestUri,
            $message,
            $contextJson
        );
    }
    
    /**
     * Belirli bir tarihe ait logları getirir
     */
    public function getLogsByDate($date, $type = 'error') {
        $logFile = $type === 'error' ? $this->errorLogPath : $this->accessLogPath;
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $handle = fopen($logFile, 'r');
        
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, $date) !== false) {
                $logs[] = $line;
            }
        }
        
        fclose($handle);
        return $logs;
    }
    
    /**
     * Son N adet logu getirir
     */
    public function getLastLogs($count = 100, $type = 'error') {
        $logFile = $type === 'error' ? $this->errorLogPath : $this->accessLogPath;
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $lines = array_reverse(file($logFile));
        
        for ($i = 0; $i < min($count, count($lines)); $i++) {
            $logs[] = $lines[$i];
        }
        
        return $logs;
    }
    
    /**
     * Log dosyalarını temizler
     */
    public function clearLogs($type = 'all') {
        if ($type === 'error' || $type === 'all') {
            file_put_contents($this->errorLogPath, '');
        }
        
        if ($type === 'access' || $type === 'all') {
            file_put_contents($this->accessLogPath, '');
        }
    }
    
    /**
     * Log dosyalarını yedekler
     */
    public function backupLogs() {
        $backupDir = $this->logPath . '/backup';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $date = date('Y-m-d_H-i-s');
        
        copy(
            $this->errorLogPath,
            $backupDir . '/error_' . $date . '.log'
        );
        
        copy(
            $this->accessLogPath,
            $backupDir . '/access_' . $date . '.log'
        );
        
        // Eski logları temizle
        $this->clearLogs();
    }
} 