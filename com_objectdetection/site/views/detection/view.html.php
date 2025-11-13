<?php

# site/views/detection/view.html.php

defined('_JEXEC') or die;

class ObjectdetectionViewDetection extends JViewLegacy
{
    public function display($tpl = null)
    {
        // Load the language file
        $this->loadLanguage();
        
        $layout = $this->getLayout();
        
        if ($layout === 'result') {
            $model = $this->getModel();
            $this->result = $model->getDetectionResult();
            
            if (!$this->result || !$this->result['success']) {
                JFactory::getApplication()->enqueueMessage(JText::_('COM_OBJECTDETECTION_NO_RESULTS'), 'error');
                JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_objectdetection', false));
                return;
            }
        }
        
        // Add CSS and JS
        $doc = JFactory::getDocument();
        $doc->addStyleSheet(JURI::root(true) . '/media/com_objectdetection/css/objectdetection.css');
        $doc->addScript(JURI::root(true) . '/media/com_objectdetection/js/objectdetection.js');
        
        parent::display($tpl);
    }
    
    protected function loadLanguage()
    {
        $lang = JFactory::getLanguage();
        $extension = 'com_objectdetection';
        $base_dir = JPATH_SITE;
        $language_tag = $lang->getTag();
        $reload = true;
        
        $lang->load($extension, $base_dir, $language_tag, $reload);
    }
    
    /**
     * Получает список групп пользователей Joomla
     */
    public function getUserGroups()
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