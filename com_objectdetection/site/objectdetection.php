<?php
defined('_JEXEC') or die;

# site/objectdetection.php

$controller = JControllerLegacy::getInstance('Objectdetection');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();