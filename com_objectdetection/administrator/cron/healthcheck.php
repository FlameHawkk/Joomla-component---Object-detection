#!/usr/bin/env php
<?php
/**
 * Health Check Cron Script for Object Detection Servers
 * Usage: php healthcheck.php
 */

// Базовый путь Joomla - ИСПРАВЛЕННЫЙ ПУТЬ
define('_JEXEC', 1);
define('JPATH_BASE', dirname(dirname(dirname(__FILE__)))); // ← ИСПРАВЛЕНО
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
        // ПУТЬ К JSON ФАЙЛУ - ИСПРАВЛЕННЫЙ
        $this->serversFile = JPATH_BASE . '/administrator/components/com_objectdetection/servers.json';
        
        // Создаем папку logs если не существует
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function run()
    {
        $this->log("=== Health Check Started ===");
        
        $servers = $this->loadServers();
        if (empty($servers)) {
            $this->log("No servers found in configuration");
            return;
        }
        
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
            $this->log("Servers file not found: " . $this->serversFile);
            return [];
        }
        
        $json = file_get_contents($this->serversFile);
        $data = json_decode($json, true);
        
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
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $healthUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Joomla-ObjectDetection-HealthCheck/1.0'
            ]);
            
            $response = curl_exec($ch);
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
        
        // Можно добавить отправку email при проблемах
        if (count($offline) > 0) {
            $this->sendAlert($offline);
        }
    }
    
    protected function sendAlert($offlineServers)
    {
        // Здесь можно добавить отправку email, SMS и т.д.
        $this->log("ALERT: " . count($offlineServers) . " servers are offline!");
        
        foreach ($offlineServers as $server) {
            $this->log("OFFLINE: " . $server['server_name'] . " - " . $server['server_url']);
        }
    }
    
    protected function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $logMessage;
        
        // Запись в файл
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Запуск скрипта
try {
    $healthCheck = new ObjectDetectionHealthCheck();
    $healthCheck->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
?>