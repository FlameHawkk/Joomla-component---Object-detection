<?php

# administrator/controllers/detection.php

defined('_JEXEC') or die;

class ObjectdetectionControllerDetection extends JControllerLegacy
{
    public function saveSettings()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        
        $app = JFactory::getApplication();
        $data = $app->input->post->get('jform', array(), 'array');
        
        if (isset($data['servers']) && is_array($data['servers'])) {
            foreach ($data['servers'] as $index => &$server) {
                // Убеждаемся, что user_groups - массив
                if (isset($server['user_groups']) && !is_array($server['user_groups'])) {
                    $server['user_groups'] = array($server['user_groups']);
                }
            }
            unset($server);
            
            // Reindex servers array
            $data['servers'] = array_values($data['servers']);
        }
        
        $params = JComponentHelper::getParams('com_objectdetection');
        
        foreach ($data as $key => $value) {
            $params->set($key, $value);
        }
        
        $componentid = JComponentHelper::getComponent('com_objectdetection')->id;
        $table = JTable::getInstance('extension');
        $table->load($componentid);
        $table->bind(array('params' => $params->toString()));
        
        if ($table->check() && $table->store()) {
            // ✅ ДОБАВЛЕНО: Сохраняем серверы в JSON файл
            if (isset($data['servers']) && is_array($data['servers'])) {
                $this->saveServersToJsonFile($data['servers']);
            }
            
            $app->enqueueMessage(JText::_('COM_OBJECTDETECTION_SETTINGS_SAVED'), 'success');
        }
        
        $app->redirect(JRoute::_('index.php?option=com_objectdetection&saved=1', false));
    }

    /**
     * Сохраняет серверы в JSON файл (встроенный метод)
     */
    protected function saveServersToJsonFile($servers)
    {
        $filePath = JPATH_ADMINISTRATOR . '/components/com_objectdetection/servers.json';
        
        $data = [
            'servers' => $servers,
            'updated' => JFactory::getDate()->toSql()
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($filePath, $json) === false) {
            JLog::add('Failed to save servers to JSON file', JLog::ERROR, 'com_objectdetection');
            return false;
        }
        
        return true;
    }
    
}