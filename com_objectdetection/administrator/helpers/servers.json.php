<?php
defined('_JEXEC') or die;

/**
 * Helper для работы с JSON файлом серверов
 */
class ObjectdetectionServersHelper
{
    protected static function getFilePath()
    {
        // ПУТЬ К ФАЙЛУ В КОМПОНЕНТЕ:
        return JPATH_ADMINISTRATOR . '/components/com_objectdetection/servers.json';
    }
    
    /**
     * Сохраняет серверы в JSON файл
     */
    public static function saveServersToFile($servers)
    {
        $filePath = self::getFilePath();
        
        $data = [
            'servers' => $servers,
            'updated' => JFactory::getDate()->toSql()
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($filePath, $json) === false) {
            JLog::add('Failed to save servers to JSON file: ' . $filePath, JLog::ERROR, 'com_objectdetection');
            return false;
        }
        
        return true;
    }
    
    /**
     * Загружает серверы из JSON файла
     */
    public static function loadServersFromFile()
    {
        $filePath = self::getFilePath();
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        
        return $data['servers'] ?? [];
    }
}