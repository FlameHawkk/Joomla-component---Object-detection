<?php

# administrator/controllers/objectdetection.php

defined('_JEXEC') or die;

class ObjectdetectionController extends JControllerLegacy
{
    protected $default_view = 'detection';
    
    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable, $urlparams);
        return $this;
    }
}