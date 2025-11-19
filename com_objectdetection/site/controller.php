<?php
defined('_JEXEC') or die;

# site/controller.php

class ObjectdetectionController extends JControllerLegacy
{
    protected $default_view = 'detection';
    
    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable, $urlparams);
        return $this;
    }
    
    /**
     * Обрабатывает выбор сервера пользователем
     */
    public function selectServer()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit('Invalid Token');
        
        $app = JFactory::getApplication();
        $serverId = $app->input->getInt('server_id', 0);
        
        // Сохраняем выбранный сервер в сессии
        $app->setUserState('com_objectdetection.selected_server', $serverId);
        
        // Redirect back to component page
        $app->redirect(JRoute::_('index.php?option=com_objectdetection', false));
    }
    
    /**
     * Основной метод для детекции
     */
    public function detect()
    {
        JSession::checkToken() or jexit('Invalid Token');
        
        $app = JFactory::getApplication();
        
        // Загружаем модель
        JModelLegacy::addIncludePath(JPATH_COMPONENT . '/models');
        $model = $this->getModel('Detection', 'ObjectdetectionModel');
        
        // Если модель не загрузилась, пробуем альтернативный способ
        if (!$model) {
            require_once JPATH_COMPONENT . '/models/detection.php';
            $model = new ObjectdetectionModelDetection();
        }
        
        if (!$model) {
            $app->enqueueMessage('Не удалось загрузить модель обработки', 'error');
            $app->redirect(JRoute::_('index.php?option=com_objectdetection'));
            return false;
        }
        
        // Получаем параметры компонента для логирования
        $params = JComponentHelper::getParams('com_objectdetection');
        $enableLogging = $params->get('enable_logging', 1);
        
        // ВАЖНО: Получаем server_id из запроса
        $serverId = $app->input->getInt('server_id', 0);
        
        // ДЕБАГ
        if ($enableLogging) {
            JLog::add("Detection request - server_id from input: " . $serverId, JLog::DEBUG, 'com_objectdetection');
        }
        
        // Если server_id не передан, используем из сессии
        if (!$serverId) {
            $serverId = $app->getUserState('com_objectdetection.selected_server', 0);
            if ($enableLogging) {
                JLog::add("Using server_id from session: " . $serverId, JLog::DEBUG, 'com_objectdetection');
            }
        }
        
        // ВАЖНО: НЕ делаем автоматический fallback! Используем ТОЛЬКО выбранный сервер
        $availableServers = $model->getAvailableServers();
        
        // Проверяем, доступен ли ВЫБРАННЫЙ сервер
        if (!isset($availableServers[$serverId])) {
            $app->enqueueMessage('Выбранный сервер недоступен. Пожалуйста, выберите другой сервер.', 'error');
            $app->redirect(JRoute::_('index.php?option=com_objectdetection'));
            return false;
        }
        
        // Сохраняем выбранный сервер в сессии
        $app->setUserState('com_objectdetection.selected_server', $serverId);
        
        $file = $_FILES['image'];
        $confidence = $app->input->getFloat('confidence_threshold', 0.5);
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(JText::_('COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE'), 'error');
            $app->redirect(JRoute::_('index.php?option=com_objectdetection'));
            return false;
        }
        
        // Process detection с указанным server_id
        $result = $model->processDetection($file, $confidence, $serverId);
        
        if ($result['success']) {
            $app->setUserState('com_objectdetection.result', $result);
            $app->redirect(JRoute::_('index.php?option=com_objectdetection&view=detection&layout=result'));
        } else {
            $app->enqueueMessage($result['error'] ?? JText::_('COM_OBJECTDETECTION_PROCESSING_ERROR'), 'error');
            $app->redirect(JRoute::_('index.php?option=com_objectdetection'));
        }
    }
}