#!/usr/local/bin/php8.4
<?php
/**
 * Health Check Script for Object Detection Servers
 * PHP 8.4 Compatible Version
 */

// ================== ОТНОСИТЕЛЬНЫЕ ПУТИ ==================
$scriptDir = __DIR__;
$joomlaRoot = dirname(dirname(dirname(dirname($scriptDir))));

define('_JEXEC', 1);
define('JPATH_BASE', $joomlaRoot);

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Проверка что скрипт запущен из командной строки
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

class ObjectDetectionHealthCheck
{
    protected $logFile;
    protected $serversFile;
    
    public function __construct()
    {
        $this->logFile = JPATH_BASE . '/logs/com_objectdetection_health.log';
        $this->serversFile = JPATH_BASE . '/administrator/components/com_objectdetection/servers.json';
        
        $this->log("=== Health Check Started ===");
        $this->log("PHP Version: " . PHP_VERSION);
        $this->log("Joomla Root: " . JPATH_BASE);
        $this->log("Servers file: " . $this->serversFile);
        
        // Создаем папку logs если не существует
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function run()
    {
        $servers = $this->loadServers();
        
        if (empty($servers)) {
            $this->log("No servers found in configuration");
            $this->log("Please save settings in component admin first");
            return;
        }
        
        $this->log("Found " . count($servers) . " servers to check");
        
        $results = [];
        
        foreach ($servers as $index => $server) {
            $result = $this->checkServerHealth($server);
            $results[] = $result;
            $this->logServerResult($result);
        }
        
        $this->generateReport($results);
        $this->log("=== Health Check Completed ===");
    }
    
    protected function loadServers()
    {
        if (!file_exists($this->serversFile)) {
            $this->log("ERROR: Servers file not found: " . $this->serversFile);
            return [];
        }
        
        $json = file_get_contents($this->serversFile);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("JSON decode error: " . json_last_error_msg());
            return [];
        }
        
        return $data['servers'] ?? [];
    }
    
    protected function checkServerHealth($server)
    {
        $server = (object) $server;
        $startTime = microtime(true);
        
        $result = [
            'server_name' => $server->server_name ?? 'Unknown',
            'server_url' => $server->server_url ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => false,
            'response_time' => 0,
            'http_code' => 0,
            'error' => ''
        ];
        
        try {
            $healthUrl = rtrim($server->server_url, '/') . '/health';
            $this->log("Checking: " . $server->server_name . " (" . $healthUrl . ")");
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $healthUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Joomla-ObjectDetection-HealthCheck/1.0',
                CURLOPT_NOBODY => true
            ]);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result['http_code'] = $httpCode;
            $result['response_time'] = $responseTime;
            $result['success'] = ($httpCode === 200);
            
            if ($error) {
                $result['error'] = $error;
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    protected function logServerResult($result)
    {
        $status = $result['success'] ? '✅ ONLINE' : '❌ OFFLINE';
        $message = sprintf(
            "Server: %s | %s | HTTP: %d | Response: %dms | Error: %s",
            $result['server_name'],
            $status,
            $result['http_code'],
            $result['response_time'],
            $result['error'] ?: 'None'
        );
        
        $this->log($message);
    }
    
    protected function generateReport($results)
    {
        $online = array_filter($results, function($r) { return $r['success']; });
        $offline = array_filter($results, function($r) { return !$r['success']; });
        
        $report = sprintf(
            "Health Report: %d/%d servers online | %d offline",
            count($online),
            count($results),
            count($offline)
        );
        
        $this->log($report);
        
        // Отправляем алерт если есть оффлайн серверы
        if (count($offline) > 0) {
            $this->sendAlert($offline);
        }
    }
    
    protected function sendAlert($offlineServers)
    {
        $this->log("ALERT: " . count($offlineServers) . " servers are offline!");
        
        foreach ($offlineServers as $server) {
            $this->log("OFFLINE SERVER: " . $server['server_name'] . " - " . $server['server_url']);
        }
    }
    
    protected function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Запуск скрипта
try {
    $healthCheck = new ObjectDetectionHealthCheck();
    $healthCheck->run();
    exit(0);
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>