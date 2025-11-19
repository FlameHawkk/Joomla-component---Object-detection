#!/usr/bin/env php
<?php
/**
 * Cleanup Script for Object Detection Images
 * Deletes files older than 1 hour
 * Usage: php cleanup_images.php
 */

// Базовый путь Joomla
define('_JEXEC', 1);
define('JPATH_BASE', dirname(dirname(dirname(dirname(__DIR__)))));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// Проверка что скрипт запущен из командной строки
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

class ObjectDetectionImageCleanup
{
    protected $logFile;
    protected $imagesPath;
    protected $maxAgeHours;
    
    public function __construct($maxAgeHours = 1)
    {
        $this->logFile = JPATH_BASE . '/logs/com_objectdetection_cleanup.log';
        $this->imagesPath = JPATH_BASE . '/images/com_objectdetection';
        $this->maxAgeHours = $maxAgeHours;
        
        // Создаем папку logs если не существует
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        
        $this->log("=== Image Cleanup Started ===");
        $this->log("Images path: " . $this->imagesPath);
        $this->log("Max age: " . $this->maxAgeHours . " hours");
    }
    
    public function run()
    {
        if (!is_dir($this->imagesPath)) {
            $this->log("ERROR: Images directory not found: " . $this->imagesPath);
            return;
        }
        
        $deletedCount = $this->cleanupDirectory($this->imagesPath);
        
        $this->log("Cleanup completed. Deleted files: " . $deletedCount);
        $this->log("=== Image Cleanup Finished ===");
    }
    
    protected function cleanupDirectory($directory)
    {
        $deletedCount = 0;
        $cutoffTime = time() - ($this->maxAgeHours * 3600);
        
        $this->log("Scanning directory: " . $directory);
        
        // Рекурсивно сканируем папку
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileAge = $file->getMTime();
                $filePath = $file->getPathname();
                $fileSize = $file->getSize();
                
                // Проверяем возраст файла
                if ($fileAge < $cutoffTime) {
                    if (unlink($filePath)) {
                        $this->log("DELETED: " . basename($filePath) . " (Size: " . $this->formatBytes($fileSize) . ", Age: " . $this->formatAge(time() - $fileAge) . ")");
                        $deletedCount++;
                    } else {
                        $this->log("ERROR: Could not delete: " . basename($filePath));
                    }
                }
            }
        }
        
        // Очищаем пустые папки
        $this->cleanupEmptyDirectories($directory);
        
        return $deletedCount;
    }
    
    protected function cleanupEmptyDirectories($directory)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $dirPath = $file->getPathname();
                
                // Проверяем, пуста ли папка
                if (count(scandir($dirPath)) == 2) {
                    if (rmdir($dirPath)) {
                        $this->log("REMOVED EMPTY DIRECTORY: " . str_replace($this->imagesPath . '/', '', $dirPath));
                    }
                }
            }
        }
    }
    
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    protected function formatAge($seconds)
    {
        $units = [
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1
        ];
        
        foreach ($units as $name => $divisor) {
            if ($seconds >= $divisor) {
                $value = floor($seconds / $divisor);
                return $value . ' ' . $name . ($value > 1 ? 's' : '');
            }
        }
        
        return 'less than a second';
    }
    
    protected function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// ================== ЗАПУСК СКРИПТА ==================
try {
    // Можно изменить время хранения (в часах)
    $maxAgeHours = isset($argv[1]) ? intval($argv[1]) : 1;
    
    $cleanup = new ObjectDetectionImageCleanup($maxAgeHours);
    $cleanup->run();
    exit(0);
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>