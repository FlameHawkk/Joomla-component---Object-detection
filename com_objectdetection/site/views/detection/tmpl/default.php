<?php defined('_JEXEC') or die; 

# site/views/detection/tmpl/default.php

// Функция для безопасного получения значений
function getServerValue($server, $key, $default = '') {
    // Проверяем, является ли $server объектом или массивом
    if (is_object($server)) {
        return isset($server->$key) ? $server->$key : $default;
    } elseif (is_array($server)) {
        return isset($server[$key]) ? $server[$key] : $default;
    }
    return $default;
}

// Получаем доступные серверы для текущего пользователя
$user = JFactory::getUser();
$model = $this->getModel();
$availableServers = $model->getAvailableServers();

// Получаем выбранный сервер (из сессии или первый доступный)
$app = JFactory::getApplication();
$selectedServerId = $app->getUserState('com_objectdetection.selected_server', 0);
$currentServer = null;

if (!empty($availableServers)) {
    if (isset($availableServers[$selectedServerId])) {
        $currentServer = $availableServers[$selectedServerId];
    } else {
        $currentServer = reset($availableServers);
        $selectedServerId = key($availableServers);
    }
}
?>

<div class="object-detection-container">
    <div class="component-header">
        <h1><?php echo JText::_('COM_OBJECTDETECTION_OBJECT_DETECTION_TITLE'); ?></h1>
        <p class="component-description"><?php echo JText::_('COM_OBJECTDETECTION_OBJECT_DETECTION_DESCRIPTION'); ?></p>
    </div>
    
    <!-- Выбор сервера (если нету серверов) -->
    <?php if (empty($availableServers)): ?>
    <div class="alert alert-warning text-center">
        <h4><?php echo JText::_('COM_OBJECTDETECTION_NO_AVAILABLE_MODELS_TITLE'); ?></h4>
        <p class="mb-0"><?php echo JText::_('COM_OBJECTDETECTION_NO_AVAILABLE_MODELS_DESC'); ?></p>
    </div>
    <?php return; endif; ?>

    <!-- Выбор сервера (если доступно несколько) -->
    <?php if (count($availableServers) > 1): ?>
    <div class="server-selection card border-light mb-4">
        <div class="card-body">
            <h5 class="card-title"><?php echo JText::_('COM_OBJECTDETECTION_SELECT_SERVER_PROMPT'); ?></h5>
            <form method="post" action="<?php echo JRoute::_('index.php?option=com_objectdetection&task=selectServer'); ?>" class="row g-3 align-items-center">
                <div class="col-md-12">
                    <select name="server_id" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($availableServers as $id => $server): ?>
                        <option value="<?php echo $id; ?>" <?php echo $id == $selectedServerId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($server->server_name); ?> 
                            (<?php echo JText::_('COM_OBJECTDETECTION_MODEL'); ?>: <?php echo htmlspecialchars($server->model_name); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php echo JHtml::_('form.token'); ?>
            </form>
        </div>
    </div>
    <?php endif; ?>
        
    <!-- Информация о текущем сервере -->
    <?php if ($currentServer): ?>
    <div class="server-info alert alert-info mb-4">
        <div class="d-flex">
            <div class="me-3">
                <span class="icon-server icon-large" aria-hidden="true"></span>
            </div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2"><?php echo JText::_('COM_OBJECTDETECTION_CURRENT_MODEL'); ?>: <?php echo htmlspecialchars(getServerValue($currentServer, 'server_name', 'Неизвестный сервер')); ?></h6>
                <div class="row small">
                    <div class="col-md-3">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_MODEL'); ?>:</strong> <?php echo htmlspecialchars(getServerValue($currentServer, 'model_name', 'Не указана')); ?>
                    </div>
                    <div class="col-md-3">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_TYPE'); ?>:</strong> <?php echo JText::_('COM_OBJECTDETECTION_SERVER_TYPE_STANDARD'); ?>
                    </div>
                    <div class="col-md-3">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_MAX_SIZE'); ?>:</strong> <?php echo htmlspecialchars(getServerValue($currentServer, 'max_file_size', 10)); ?> MB
                    </div>
                    <div class="col-md-3">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_LANGUAGE'); ?>:</strong> <?php echo getServerValue($currentServer, 'detection_language', 'en') == 'ru' ? 'Русский' : 'English'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Скрытые элементы для JavaScript translations -->
    <div style="display: none;" id="js-translations">
        <span data-string="COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE"><?php echo JText::_('COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE'); ?></span>
        <span data-string="COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE_FILE"><?php echo JText::_('COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE_FILE'); ?></span>
        <span data-string="COM_OBJECTDETECTION_PROCESSING"><?php echo JText::_('COM_OBJECTDETECTION_PROCESSING'); ?></span>
    </div>
    
    <div class="upload-section card border-light">
    <div class="card-body">
        <form id="upload-form" method="post" enctype="multipart/form-data" 
              action="<?php echo JRoute::_('index.php?option=com_objectdetection&task=detect'); ?>">
            <?php echo JHtml::_('form.token'); ?>
            
            <!-- Скрытое поле для выбранного сервера -->
            <?php if ($currentServer): ?>
            <input type="hidden" name="server_id" value="<?php echo $selectedServerId; ?>" />
            <?php endif; ?>
            
            <div class="form-group">
                <div id="drop-area" class="drop-zone">
                    <div class="drop-zone__prompt">
                        <div class="drop-icon">
                            <span class="icon-upload" aria-hidden="true"></span>
                        </div>
                        <span class="drop-text"><?php echo JText::_('COM_OBJECTDETECTION_DRAG_DROP'); ?></span>
                        <span class="drop-or"><?php echo JText::_('COM_OBJECTDETECTION_OR'); ?></span>
                        <button type="button" class="btn btn-outline-primary browse-btn"><?php echo JText::_('COM_OBJECTDETECTION_SELECT_FILE'); ?></button>
                        <small class="file-types">
                            <?php 
                            $maxFileSize = $currentServer ? getServerValue($currentServer, 'max_file_size', 20) : 20;
                            echo JText::sprintf('COM_OBJECTDETECTION_SUPPORTED_FORMATS_PARAM', 'JPG, PNG, GIF, WebP', $maxFileSize); 
                            ?>
                        </small>
                    </div>
                    <div id="preview" class="preview-container" style="display: none;">
                        <div class="preview-wrapper">
                            <img id="preview-image" src="#" alt="Preview" class="img-fluid" />
                            <button type="button" id="remove-file" class="remove-btn" title="<?php echo JText::_('COM_OBJECTDETECTION_REMOVE_FILE'); ?>">
                                <span class="icon-cancel" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="file-info">
                            <span id="file-name" class="file-name"></span>
                            <span class="file-size text-muted" id="file-size"></span>
                        </div>
                    </div>
                </div>
                <input type="file" name="image" id="image-input" accept="image/*" style="display: none;" required>
            </div>
            
            <div class="form-group">
                <label for="confidence-slider" class="form-label">
                    <?php echo JText::_('COM_OBJECTDETECTION_CONFIDENCE_THRESHOLD'); ?>: 
                    <span id="confidence-value" class="badge bg-secondary">
                        <?php echo $currentServer ? getServerValue($currentServer, 'default_confidence', 0.5) : 0.5; ?>
                    </span>
                </label>
                <input type="range" id="confidence-slider" name="confidence_threshold" 
                       min="0.1" max="0.9" step="0.1" 
                       value="<?php echo $currentServer ? getServerValue($currentServer, 'default_confidence', 0.5) : 0.5; ?>" 
                       class="form-range">
                <div class="form-text text-small">
                    <?php echo JText::_('COM_OBJECTDETECTION_CONFIDENCE_DESCRIPTION'); ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success w-100" id="detect-btn" disabled>
                <span class="icon-search" aria-hidden="true"></span>
                <?php echo JText::_('COM_OBJECTDETECTION_DETECT_OBJECTS'); ?>
            </button>
        </form>
    </div>
</div>