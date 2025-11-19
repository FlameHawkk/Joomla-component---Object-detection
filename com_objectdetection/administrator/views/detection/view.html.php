<?php

# administrator/views/detection/view.html.php

defined('_JEXEC') or die;

class ObjectdetectionViewDetection extends JViewLegacy
{
    protected $params;
    protected $apiStatus;
    
    public function display($tpl = null)
    {
        // Get component parameters
        $this->params = JComponentHelper::getParams('com_objectdetection');
        
        // Test API connection for the first active server
        $servers = $this->params->get('servers', array());
        $apiUrl = 'https://yolo-api.onrender.com';
        
        if (!empty($servers)) {
            foreach ($servers as $server) {
                if ($server->is_active) {
                    $apiUrl = $server->server_url;
                    break;
                }
            }
        }
        
        $this->apiStatus = $this->testApiConnection($apiUrl);
        
        // Set toolbar
        $this->addToolbar();
        
        parent::display($tpl);
    }

    /**
     * Форматирует размер файла в читаемый вид
     */
    public function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        
        return number_format(($bytes / pow($k, $i)), 2) . ' ' . $sizes[$i];
    }
    
    protected function addToolbar()
    {
        JToolbarHelper::title(JText::_('COM_OBJECTDETECTION_ADMIN_TITLE'), 'cog');
        
        // Add save button
        JToolbarHelper::apply('detection.saveSettings');
        
        // Add frontend link button
        $bar = JToolbar::getInstance('toolbar');
        $frontend_url = JUri::root() . 'index.php?option=com_objectdetection';
        $bar->appendButton('Link', 'eye', 'COM_OBJECTDETECTION_VIEW_FRONTEND', $frontend_url);
    }
    
    protected function testApiConnection($apiUrl)
    {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl . '/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_NOBODY => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            return [
                'success' => $httpCode === 200,
                'http_code' => $httpCode,
                'error' => $error
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получает список групп пользователей Joomla
     */
    protected function getUserGroups()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from('#__usergroups')
            ->order('lft ASC');
        
        $db->setQuery($query);
        return $db->loadObjectList();
    }
}