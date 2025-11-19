<?php

# script.php

defined('_JEXEC') or die;

class Com_ObjectdetectionInstallerScript
{
    public function install($parent)
    {
        // Create media directories
        $mediaPath = JPATH_ROOT . '/media/com_objectdetection';
        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0755, true);
            mkdir($mediaPath . '/css', 0755, true);
            mkdir($mediaPath . '/js', 0755, true);
        }
        
        // Create images directory
        $imagesPath = JPATH_ROOT . '/images/com_objectdetection';
        if (!is_dir($imagesPath)) {
            mkdir($imagesPath, 0755, true);
        }
        
        // Set default parameters
        $this->setDefaultParameters();
        
        return true;
    }
    
    public function uninstall($parent)
    {
        return true;
    }
    
    public function update($parent)
    {
        // Update parameters if needed
        $this->setDefaultParameters();
        
        // Create images directory if it doesn't exist
        $imagesPath = JPATH_ROOT . '/images/com_objectdetection';
        if (!is_dir($imagesPath)) {
            mkdir($imagesPath, 0755, true);
        }
        
        return true;
    }
    
    private function setDefaultParameters()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        
        $params = array(
            'api_url' => 'https://yolo-api-d44x.onrender.com',
            'max_file_size' => 10, // 10 MB по умолчанию
            'timeout' => 120,
            'default_confidence' => 0.5,
            'model_name' => 'yolov8n'
        );
        
        $query->update($db->quoteName('#__extensions'))
              ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
              ->where($db->quoteName('element') . ' = ' . $db->quote('com_objectdetection'))
              ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        
        $db->setQuery($query);
        
        try {
            $db->execute();
        } catch (Exception $e) {
            // Ignore errors during parameter update
        }
    }
}