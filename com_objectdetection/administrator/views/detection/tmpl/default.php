<?php defined('_JEXEC') or die; 

# administrator/views/detection/tmpl/default.php

JHtml::_('jquery.framework');

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
?>

<div class="container-fluid">
    <div class="row-fluid">
        <div class="span12">
            <h2><?php echo JText::_('COM_OBJECTDETECTION_ADMIN_TITLE'); ?></h2>
            
            <form action="<?php echo JRoute::_('index.php?option=com_objectdetection&task=detection.saveSettings'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
                
                <!-- Секция серверов -->
                <div class="well">
                    <h3><?php echo JText::_('COM_OBJECTDETECTION_SERVERS_SETTINGS'); ?></h3>
                    <p class="small"><?php echo JText::_('COM_OBJECTDETECTION_SERVERS_SETTINGS_DESC'); ?></p>
                    
                    <div id="servers-container">
                        <?php
                        $servers = $this->params->get('servers', array());
                        if (empty($servers)) {
                            // Добавляем сервер по умолчанию с пустым URL
                            $servers = array(
                                (object)array(
                                    'server_name' => 'Основной сервер',
                                    'server_url' => 'https://yolo-api-example.onrender.com',
                                    'model_name' => 'yolov8n',
                                    'max_file_size' => 10,
                                    'timeout' => 120,
                                    'default_confidence' => 0.5,
                                    'detection_language' => 'en',
                                    'is_active' => 1,
                                    'user_groups' => array(2)
                                )
                            );
                        }
                        
                        foreach ($servers as $index => $server):
                            $server = (object)$server;
                        ?>
                        <div class="server-config card mb-3" data-index="<?php echo $index; ?>">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars(getServerValue($server, 'server_name', JText::_('COM_OBJECTDETECTION_NEW_SERVER'))); ?>
                                </h5>
                                <button type="button" class="btn btn-sm btn-danger remove-server" style="display: <?php echo count($servers) > 1 ? 'block' : 'none'; ?>;">
                                    <span class="icon-trash"></span> <?php echo JText::_('COM_OBJECTDETECTION_REMOVE_SERVER'); ?> 
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_SERVER_NAME_LABEL'); ?></label>
                                            <input type="text" name="jform[servers][<?php echo $index; ?>][server_name]" 
                                                   value="<?php echo htmlspecialchars(getServerValue($server, 'server_name', JText::_('COM_OBJECTDETECTION_NEW_SERVER'))); ?>" 
                                                   class="form-control dark-input" required />
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_SERVER_NAME_DESC'); ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_SERVER_URL_LABEL'); ?></label>
                                            <div class="input-group">
                                                <input type="url" name="jform[servers][<?php echo $index; ?>][server_url]" 
                                                    value="<?php echo htmlspecialchars(getServerValue($server, 'server_url', 'https://your-render-service.onrender.com')); ?>" 
                                                    class="form-control dark-input" required />
                                                <button type="button" class="btn btn-outline-warning wake-server-btn" 
                                                        data-server-url="<?php echo htmlspecialchars(getServerValue($server, 'server_url', 'https://your-render-service.onrender.com')); ?>">
                                                    <span class="icon-play"></span> <?php echo JText::_('COM_OBJECTDETECTION_WAKE_SERVER'); ?>
                                                </button>
                                            </div>
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_SERVER_URL_DESC'); ?></div>
                                        </div>
                                        
                                        <!-- Выбор модели -->
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_YOLO_MODEL_NAME'); ?></label>
                                            <div class="input-group">
                                                <input type="text" name="jform[servers][<?php echo $index; ?>][model_name]" 
                                                    value="<?php echo htmlspecialchars(getServerValue($server, 'model_name', 'yolov8n.pt')); ?>" 
                                                    class="form-control dark-input" 
                                                    placeholder="yolov8n.pt, yolov10n.pt, etc." required />
                                            </div>
                                            <div class="form-text">
                                                <?php echo JText::_('COM_OBJECTDETECTION_YOLO_MODEL_DESC'); ?>
                                            </div> 
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_USER_GROUPS_LABEL'); ?></label>
                                            <?php
                                            $userGroups = $this->getUserGroups();
                                            $selectedGroups = isset($server->user_groups) ? (array)$server->user_groups : array(2);
                                            ?>
                                            <select name="jform[servers][<?php echo $index; ?>][user_groups][]" 
                                                    class="form-control dark-input" multiple="multiple" size="6">
                                                <?php foreach ($userGroups as $group): ?>
                                                <option value="<?php echo $group->value; ?>" 
                                                        <?php echo in_array($group->value, $selectedGroups) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($group->text); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_USER_GROUPS_DESC'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">                                                                   
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_MAX_FILE_SIZE_LABEL'); ?></label>
                                            <input type="number" name="jform[servers][<?php echo $index; ?>][max_file_size]" 
                                                   value="<?php echo htmlspecialchars(getServerValue($server, 'max_file_size', 5)); ?>" 
                                                   min="1" max="50" class="form-control dark-input" required />
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_MAX_FILE_SIZE_DESC'); ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_TIMEOUT_LABEL'); ?></label>
                                            <input type="number" name="jform[servers][<?php echo $index; ?>][timeout]" 
                                                   value="<?php echo htmlspecialchars(getServerValue($server, 'timeout', 120)); ?>" 
                                                   min="30" max="300" class="form-control dark-input" required />
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_TIMEOUT_DESC'); ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_DEFAULT_CONFIDENCE_LABEL'); ?></label>
                                            <input type="number" name="jform[servers][<?php echo $index; ?>][default_confidence]" 
                                                   value="<?php echo htmlspecialchars(getServerValue($server, 'default_confidence', 0.5)); ?>" 
                                                   min="0.1" max="0.9" step="0.1" class="form-control dark-input" required />
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_DEFAULT_CONFIDENCE_DESC'); ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo JText::_('COM_OBJECTDETECTION_LANGUAGE_LABEL'); ?></label>
                                            <select name="jform[servers][<?php echo $index; ?>][detection_language]" class="form-control dark-input">
                                                <option value="en" <?php echo getServerValue($server, 'detection_language', 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="ru" <?php echo getServerValue($server, 'detection_language', 'en') == 'ru' ? 'selected' : ''; ?>>Russian</option>
                                            </select>
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_LANGUAGE_DESC'); ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="jform[servers][<?php echo $index; ?>][is_active]" 
                                                       value="1" class="form-check-input" id="is_active_<?php echo $index; ?>"
                                                       <?php echo getServerValue($server, 'is_active', 1) ? 'checked' : ''; ?> />
                                                <label class="form-check-label" for="is_active_<?php echo $index; ?>">
                                                    <?php echo JText::_('COM_OBJECTDETECTION_SERVER_ACTIVE_LABEL'); ?>
                                                </label>
                                            </div>
                                            <div class="form-text"><?php echo JText::_('COM_OBJECTDETECTION_SERVER_ACTIVE_DESC'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="add-server" class="btn btn-success mt-3">
                        <span class="icon-plus"></span> <?php echo JText::_('COM_OBJECTDETECTION_ADD_SERVER'); ?>
                    </button>
                </div>
                
                <!-- Расширенные настройки -->
                <!-- удалил -->
                
                <!-- Информация о текущем статусе -->
                <div class="well">                  
                    <div class="row">                      
                        <div class="col-md-6">
                            <h4><?php echo JText::_('COM_OBJECTDETECTION_SERVER_STATS'); ?></h4>
                            <?php
                            $active_servers = array();
                            foreach ($servers as $server) {
                                $server = (object)$server;
                                if (getServerValue($server, 'is_active', 1)) {
                                    $modelType = getServerValue($server, 'model_type', 'standard') == 'custom' ? ' (кастомная)' : '';
                                    $active_servers[] = getServerValue($server, 'server_name', 'Сервер') . $modelType;
                                }
                            }
                            ?>
                            <table class="table table-sm">
                                <tr>
                                    <td><?php echo JText::_('COM_OBJECTDETECTION_TOTAL_SERVERS'); ?>:</td>
                                    <td><strong><?php echo count($servers); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><?php echo JText::_('COM_OBJECTDETECTION_ACTIVE_SERVERS_COUNT'); ?>:</td>
                                    <td><strong><?php echo count($active_servers); ?></strong></td>
                                </tr>
                            </table>
                            
                            <?php if (!empty($active_servers)): ?>
                            <h5><?php echo JText::_('COM_OBJECTDETECTION_ACTIVE_SERVERS'); ?></h5>
                            <ul class="list-unstyled">
                                <?php foreach ($active_servers as $server_info): ?>
                                <li><span class="icon-check text-success"></span> <?php echo htmlspecialchars($server_info); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                                                      
                        </div>
                    </div>
                </div>         
                
                <input type="hidden" name="task" value="" />
                <?php echo JHtml::_('form.token'); ?>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {

    // Переводы для JavaScript
    const JTexts = {
        'NEW_SERVER': '<?php echo JText::_("COM_OBJECTDETECTION_NEW_SERVER"); ?>',
        'REMOVE_SERVER': '<?php echo JText::_("COM_OBJECTDETECTION_REMOVE_SERVER"); ?>',
        'SERVER_NAME_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_NAME_LABEL"); ?>',
        'SERVER_NAME_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_NAME_DESC"); ?>',
        'SERVER_URL_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_URL_LABEL"); ?>',
        'SERVER_URL_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_URL_DESC"); ?>',
        'YOLO_MODEL_NAME': '<?php echo JText::_("COM_OBJECTDETECTION_YOLO_MODEL_NAME"); ?>',
        'YOLO_MODEL_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_YOLO_MODEL_DESC"); ?>',
        'USER_GROUPS_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_USER_GROUPS_LABEL"); ?>',
        'USER_GROUPS_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_USER_GROUPS_DESC"); ?>',
        'MAX_FILE_SIZE_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_MAX_FILE_SIZE_LABEL"); ?>',
        'MAX_FILE_SIZE_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_MAX_FILE_SIZE_DESC"); ?>',
        'TIMEOUT_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_TIMEOUT_LABEL"); ?>',
        'TIMEOUT_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_TIMEOUT_DESC"); ?>',
        'DEFAULT_CONFIDENCE_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_DEFAULT_CONFIDENCE_LABEL"); ?>',
        'DEFAULT_CONFIDENCE_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_DEFAULT_CONFIDENCE_DESC"); ?>',
        'LANGUAGE_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_LANGUAGE_LABEL"); ?>',
        'LANGUAGE_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_LANGUAGE_DESC"); ?>',
        'ACTIVE_SERVER_LABEL': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_ACTIVE_LABEL"); ?>',
        'ACTIVE_SERVER_DESC': '<?php echo JText::_("COM_OBJECTDETECTION_SERVER_ACTIVE_DESC"); ?>',
        'WAKE_SERVER': '<?php echo JText::_("COM_OBJECTDETECTION_WAKE_SERVER"); ?>'
    };
    
    let serverIndex = <?php echo count($servers); ?>;    

    // Функция для "пробуждения" сервера
    function wakeServer(serverUrl, button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<span class="icon-spinner spinner"></span> Будим...';
        button.disabled = true;
        
        // ВАЖНО: Правильный URL для проверки здоровья
        const healthUrl = serverUrl.replace(/\/$/, '') + '/health';
        
        console.log('Waking server:', healthUrl);
        
        fetch(healthUrl, { 
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error('Server responded with error: ' + response.status);
            }
        })
        .then(data => {
            button.innerHTML = '<span class="icon-checkmark"></span> Успешно!';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        })
        .catch(error => {
            console.error('Wake server error:', error);
            button.innerHTML = '<span class="icon-cancel"></span> Ошибка';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });
    }

    // Обработчик для кнопок пробуждения
    $(document).on('click', '.wake-server-btn', function() {
        const serverUrl = $(this).data('server-url');
        wakeServer(serverUrl, this);
    });
         
    // Добавление нового сервера
    $('#add-server').on('click', function() {
        const newServerHtml = `
        <div class="server-config card mb-3" data-index="${serverIndex}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">${JTexts.NEW_SERVER}</h5>
                <button type="button" class="btn btn-sm btn-danger remove-server">
                    <span class="icon-trash"></span> ${JTexts.REMOVE_SERVER}
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">${JTexts.SERVER_NAME_LABEL}</label>
                            <input type="text" name="jform[servers][${serverIndex}][server_name]" 
                                value="${JTexts.NEW_SERVER}" class="form-control dark-input" required />
                            <div class="form-text">${JTexts.SERVER_NAME_DESC}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${JTexts.SERVER_URL_LABEL}</label>
                            <div class="input-group">
                                <input type="url" name="jform[servers][${serverIndex}][server_url]" 
                                    value="https://your-render-service.onrender.com" class="form-control dark-input" required />
                                <button type="button" class="btn btn-outline-warning wake-server-btn" 
                                        data-server-url="https://your-render-service.onrender.com">
                                    <span class="icon-play"></span> ${JTexts.WAKE_SERVER}
                                </button>
                            </div>
                            <div class="form-text">${JTexts.SERVER_URL_DESC}</div>
                        </div>
                        
                        <!-- ИСПРАВЛЕННЫЙ БЛОК МОДЕЛИ С ПОДСКАЗКОЙ -->
                        <div class="mb-3">
                            <label class="form-label">${JTexts.YOLO_MODEL_NAME}</label>
                            <input type="text" name="jform[servers][${serverIndex}][model_name]" 
                                value="yolov8n.pt" class="form-control dark-input" 
                                placeholder="yolov8n.pt, yolov10n.pt, etc." required />
                            <div class="form-text">${JTexts.YOLO_MODEL_DESC}</div>
                        </div>
                        
                        <!-- ПЕРЕМЕЩЕННОЕ ПОЛЕ ГРУПП ПОЛЬЗОВАТЕЛЕЙ С ПРАВИЛЬНЫМ РАЗМЕРОМ И ПОДСКАЗКОЙ -->
                        <div class="mb-3">
                            <label class="form-label">${JTexts.USER_GROUPS_LABEL}</label>
                            <select name="jform[servers][${serverIndex}][user_groups][]" class="form-control dark-input" multiple="multiple" size="4">
                                <?php foreach ($userGroups as $group): ?>
                                <option value="<?php echo $group->value; ?>" <?php echo $group->value == 2 ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group->text); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">${JTexts.USER_GROUPS_DESC}</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">${JTexts.MAX_FILE_SIZE_LABEL}</label>
                            <input type="number" name="jform[servers][${serverIndex}][max_file_size]" 
                                value="5" min="1" max="50" class="form-control dark-input" required />
                            <div class="form-text">${JTexts.MAX_FILE_SIZE_DESC}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${JTexts.TIMEOUT_LABEL}</label>
                            <input type="number" name="jform[servers][${serverIndex}][timeout]" 
                                value="120" min="30" max="300" class="form-control dark-input" required />
                            <div class="form-text">${JTexts.TIMEOUT_DESC}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${JTexts.DEFAULT_CONFIDENCE_LABEL}</label>
                            <input type="number" name="jform[servers][${serverIndex}][default_confidence]" 
                                value="0.5" min="0.1" max="0.9" step="0.1" class="form-control dark-input" required />
                            <div class="form-text">${JTexts.DEFAULT_CONFIDENCE_DESC}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">${JTexts.LANGUAGE_LABEL}</label>
                            <select name="jform[servers][${serverIndex}][detection_language]" class="form-control dark-input">
                                <option value="en">English</option>
                                <option value="ru">Russian</option>
                            </select>
                            <div class="form-text">${JTexts.LANGUAGE_DESC}</div>
                        </div>
                        
                        <!-- ИСПРАВЛЕННЫЙ ЧЕКБОКС С ПОДСКАЗКОЙ -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="jform[servers][${serverIndex}][is_active]" 
                                    value="1" class="form-check-input" id="is_active_${serverIndex}" checked />
                                <label class="form-check-label" for="is_active_${serverIndex}">
                                    ${JTexts.ACTIVE_SERVER_LABEL}
                                </label>
                            </div>
                            <div class="form-text">${JTexts.ACTIVE_SERVER_DESC}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
        
        $('#servers-container').append(newServerHtml);
        serverIndex++;
        
        // Показываем кнопки удаления у всех серверов, если их больше одного
        $('.remove-server').show();
    });
    
    // Удаление сервера
    $(document).on('click', '.remove-server', function() {
        const serverElement = $(this).closest('.server-config');
        serverElement.remove();
        
        // Скрываем кнопки удаления, если остался только один сервер
        if ($('.server-config').length <= 1) {
            $('.remove-server').hide();
        }
    });
});
</script>

<style>
.well {
    background: var(--body-bg, #f5f5f5);
    border: 1px solid var(--border-color, #e3e3e3);
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--card-border-color, rgba(0,0,0,.125));
}

.card-header {
    background: var(--card-cap-bg, rgba(0,0,0,.03)) !important;
    border-bottom: 1px solid var(--card-border-color, rgba(0,0,0,.125));
}

.form-label {
    color: var(--body-color, #212529);
    font-weight: 500;
}

.form-text {
    color: var(--text-muted, #6c757d) !important;
}

/* Стили для темной темы */
.dark-input {
    background: var(--input-bg, #fff) !important;
    border: 1px solid var(--input-border-color, #ced4da) !important;
    color: var(--body-color, #212529) !important;
}

.dark-input:focus {
    background: var(--input-focus-bg, #fff) !important;
    border-color: var(--input-focus-border-color, #86b7fe) !important;
    color: var(--body-color, #212529) !important;
}

select.dark-input {
    background: var(--input-bg, #fff) !important;
    color: var(--body-color, #212529) !important;
}

/* Адаптация для темной темы Joomla */
@media (prefers-color-scheme: dark) {
    .well {
        background: #2a2a2a;
        border-color: #444;
    }
    
    .card {
        background: #2a2a2a;
        border-color: #444;
    }
    
    .card-header {
        background: #333 !important;
        border-color: #444;
    }
    
    .dark-input {
        background: #333 !important;
        border-color: #555 !important;
        color: #eee !important;
    }
    
    .dark-input:focus {
        background: #333 !important;
        border-color: #0d6efd !important;
        color: #eee !important;
    }
    
    select.dark-input {
        background: #333 !important;
        color: #eee !important;
    }
    
    .form-label {
        color: #eee !important;
    }
    
    .form-text {
        color: #aaa !important;
    }
    
    .card-title {
        color: #eee !important;
    }
}

.btn-primary {
    background: var(--btn-primary-bg, #0d6efd);
    border-color: var(--btn-primary-border-color, #0d6efd);
}

.badge.bg-success {
    background: var(--success, #198754) !important;
}

.badge.bg-danger {
    background: var(--danger, #dc3545) !important;
}

.text-danger {
    color: var(--danger, #dc3545) !important;
}

.table {
    color: var(--body-color, #212529);
}
</style>