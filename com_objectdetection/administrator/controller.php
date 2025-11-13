<?php

# administrator/controller.php

defined('_JEXEC') or die;

class ObjectdetectionController extends JControllerLegacy
{
    protected $default_view = 'detection';
    
    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable, $urlparams);
        return $this;
    }
    
    public function getModel($name = 'Detection', $prefix = 'ObjectdetectionModel', $config = array())
    {
        return parent::getModel($name, $prefix, $config);
    }
    
    // Добавьте этот метод
    public function saveSettings()
    {
        // Check for request forgeries
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        
        $app = JFactory::getApplication();
        $data = $app->input->post->get('jform', array(), 'array');
        
        // Validate data
        if (empty($data)) {
            $app->enqueueMessage(JText::_('COM_OBJECTDETECTION_SETTINGS_EMPTY'), 'error');
            $app->redirect(JRoute::_('index.php?option=com_objectdetection', false));
            return false;
        }
        
        // Get the component parameters
        $params = JComponentHelper::getParams('com_objectdetection');
        
        // Set the new parameters
        foreach ($data as $key => $value) {
            $params->set($key, $value);
        }
        
        // Save the parameters
        $componentid = JComponentHelper::getComponent('com_objectdetection')->id;
        $table = JTable::getInstance('extension');
        $table->load($componentid);
        $table->bind(array('params' => $params->toString()));
        
        if ($table->check() && $table->store()) {
            $app->enqueueMessage(JText::_('COM_OBJECTDETECTION_SETTINGS_SAVED'), 'success');
        } else {
            $app->enqueueMessage(JText::_('COM_OBJECTDETECTION_SETTINGS_SAVE_ERROR'), 'error');
        }
        
        // Redirect back to component page
        $app->redirect(JRoute::_('index.php?option=com_objectdetection&saved=1', false));
    }
}