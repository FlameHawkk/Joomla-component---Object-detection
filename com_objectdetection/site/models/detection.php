<?php
defined('_JEXEC') or die;

# site/models/detection.php

class ObjectdetectionModelDetection extends JModelLegacy
{
    protected $params;
    
    public function __construct($config = array())
    {
        parent::__construct($config);
        // Загружаем параметры компонента
        $this->params = JComponentHelper::getParams('com_objectdetection');
    }
    
    /**
     * Получает все доступные серверы для текущего пользователя
     */
    public function getAvailableServers()
    {
        $servers = $this->params->get('servers', []);
        $user = JFactory::getUser();
        $userGroups = $user->getAuthorisedGroups();
        
        $availableServers = [];
        
        foreach ($servers as $index => $server) {
            $server = (object) $server;
            
            // Безопасная проверка is_active
            $isActive = isset($server->is_active) ? $server->is_active : true;
            
            if ($isActive && $this->userHasAccess($server, $userGroups)) {
                $availableServers[$index] = $server;
            }
        }
        
        return $availableServers;
    }
    
    /**
     * Получает сервер по ID
     */
    public function getServerById($serverId)
    {
        $servers = $this->params->get('servers', []);
        return isset($servers[$serverId]) ? (object) $servers[$serverId] : null;
    }
    
    /**
     * Основной метод обработки детекции
     */
    public function processDetection($file, $confidence = null, $serverId = null)
    {
        try {
            // Получаем сервер (по ID СТРОГО)
            $server = $this->getServer($serverId);
            
            if (!$server) {
                return ['success' => false, 'error' => 'Выбранный сервер недоступен'];
            }
            
            // Используем максимальный размер файла из настроек сервера
            $maxFileSize = $server->max_file_size * 1024 * 1024;
            
            // Проверка размера файла
            if ($file['size'] > $maxFileSize) {
                return [
                    'success' => false, 
                    'error' => JText::sprintf('COM_OBJECTDETECTION_FILE_TOO_LARGE_PARAM', $server->max_file_size)
                ];
            }
            
            // Если confidence не передан, используем значение по умолчанию из сервера
            if ($confidence === null) {
                $confidence = $server->default_confidence;
            }
            
            // Сохраняем изображение
            $uploadPath = $this->saveUploadedImage($file);
            if (!$uploadPath) {
                return ['success' => false, 'error' => JText::_('COM_OBJECTDETECTION_SAVE_ERROR')];
            }
            
            // Отправляем в YOLO API
            $apiResult = $this->sendToYoloApi($uploadPath, $confidence, $server);
            
            if ($apiResult === false) {
                return ['success' => false, 'error' => JText::_('COM_OBJECTDETECTION_API_ERROR')];
            }
            
            $detections = $apiResult['detections'];
            $annotatedImage = $apiResult['annotated_image'];
            
            // Сохраняем аннотированное изображение если доступно
            $resultPath = null;
            if ($annotatedImage) {
                $resultPath = $this->saveAnnotatedImage($annotatedImage);
            }
            
            // Логируем если включено
            if ($this->params->get('enable_logging', 1)) {
                $detectionsCount = count($detections);
                JLog::add("Detection completed on server {$server->server_name} - Objects: $detectionsCount, Confidence: $confidence", JLog::INFO, 'com_objectdetection');
            }
            
            return [
                'success' => true,
                'original_filename' => $file['name'],
                'original_image' => $uploadPath,
                'result_image' => $resultPath,
                'detections' => $detections,
                'confidence' => $confidence,
                'server_used' => $server->server_name,
                'model_used' => $server->model_name,
                'server_id' => $serverId,
                'timestamp' => JFactory::getDate()->toSql()
            ];
            
        } catch (Exception $e) {
            if ($this->params->get('enable_logging', 1)) {
                JLog::add('Detection error: ' . $e->getMessage(), JLog::ERROR, 'com_objectdetection');
            }
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получает сервер для пользователя
     */
    private function getServer($server_id = null)
    {
        $servers = $this->params->get('servers', []);
        $user = JFactory::getUser();
        $userGroups = $user->getAuthorisedGroups();
        
        // ВАЖНО: Строгая логика - используем ТОЛЬКО указанный server_id
        if ($server_id !== null) {
            if (isset($servers[$server_id])) {
                $server = (object) $servers[$server_id];
                
                // Проверяем доступ пользователя к этому серверу
                if ($server->is_active && $this->userHasAccess($server, $userGroups)) {
                    return $server;
                } else {
                    // Сервер существует, но недоступен для пользователя
                    if ($this->params->get('enable_logging', 1)) {
                        JLog::add("Server $server_id exists but not accessible for user", JLog::DEBUG, 'com_objectdetection');
                    }
                    return null;
                }
            } else {
                // Сервер не существует
                if ($this->params->get('enable_logging', 1)) {
                    JLog::add("Server $server_id not found", JLog::DEBUG, 'com_objectdetection');
                }
                return null;
            }
        }
        
        // ТОЛЬКО если server_id не указан - ищем первый доступный
        foreach ($servers as $index => $server) {
            $server = (object) $server;
            if ($server->is_active && $this->userHasAccess($server, $userGroups)) {
                return $server;
            }
        }
        
        return null;
    }
    
    /**
     * Проверяет, имеет ли пользователь доступ к серверу
     */
    private function userHasAccess($server, $userGroups)
    {
        if (!isset($server->user_groups) || empty($server->user_groups)) {
            return true; // Если группы не указаны, доступ разрешен всем
        }
        
        $serverGroups = (array)$server->user_groups;
        
        // Проверяем пересечение групп пользователя и групп сервера
        return !empty(array_intersect($userGroups, $serverGroups));
    }
    
    /**
     * Отправляет запрос к YOLO API
     */
    private function sendToYoloApi($imagePath, $confidence, $server)
    {
        $ch = curl_init();
        
        $apiUrl = $server->server_url;
        $timeout = $server->timeout;
        $language = $server->detection_language;
        
        $postData = [
            'confidence' => (float)$confidence,
            'language' => $language
        ];
        
        $fileData = new CURLFile($imagePath, mime_content_type($imagePath), basename($imagePath));
        $postData['file'] = $fileData;
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . '/predict/',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            // Логируем ошибку
            if ($this->params->get('enable_logging', 1)) {
                JLog::add("API Error - HTTP Code: $httpCode, Error: $error, Response: $response", JLog::ERROR, 'com_objectdetection');
            }
            return false;
        }
        
        return json_decode($response, true);
    }
    
    
    /**
     * Сохраняет загруженное изображение
     */
    private function saveUploadedImage($file)
    {
        $imagesDir = JPATH_ROOT . '/images/com_objectdetection';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }
        
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file['name']);
        $filepath = $imagesDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'images/com_objectdetection/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Сохраняет аннотированное изображение
     */
    private function saveAnnotatedImage($base64Image)
    {
        $imagesDir = JPATH_ROOT . '/images/com_objectdetection';
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }
        
        // Remove data:image/jpeg;base64, prefix if present
        $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $imageData = base64_decode($base64Image);
        
        if ($imageData === false) {
            return null;
        }
        
        $filename = 'result_' . uniqid() . '.jpg';
        $filepath = $imagesDir . '/' . $filename;
        
        if (file_put_contents($filepath, $imageData)) {
            return 'images/com_objectdetection/' . $filename;
        }
        
        return null;
    }
    
    /**
     * Получает результат детекции
     */
    public function getDetectionResult()
    {
        $app = JFactory::getApplication();
        return $app->getUserState('com_objectdetection.result');
    }
    
    /**
     * Очищает результат детекции
     */
    public function clearDetectionResult()
    {
        $app = JFactory::getApplication();
        $app->setUserState('com_objectdetection.result', null);
    }
    
}