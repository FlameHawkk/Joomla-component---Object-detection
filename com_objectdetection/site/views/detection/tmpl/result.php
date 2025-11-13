<?php defined('_JEXEC') or die; ?>

<!-- site/views/detection/tmpl/result.php -->

<div class="object-detection-container">
    <div class="component-header">
        <h1><?php echo JText::_('COM_OBJECTDETECTION_RESULTS_TITLE'); ?></h1>
    </div>
    
    <!-- Информация о сервере и модели -->
    <div class="server-info alert alert-info mb-4">
        <div class="d-flex">
            <div class="me-3">
                <span class="icon-server icon-large" aria-hidden="true"></span>
            </div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2"><?php echo JText::_('COM_OBJECTDETECTION_USED_SERVER'); ?>:</h6>
                <div class="row small">
                    <div class="col-md-4">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_SERVER'); ?>:</strong> <?php echo htmlspecialchars($this->result['server_used']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_MODEL'); ?>:</strong> <?php echo htmlspecialchars($this->result['model_used']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong><?php echo JText::_('COM_OBJECTDETECTION_PROCESSING_TIME'); ?>:</strong> <?php echo JHtml::_('date', $this->result['timestamp'], 'd.m.Y H:i:s'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="results-grid row">
        <div class="col-md-6">
            <div class="card border-light">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo JText::_('COM_OBJECTDETECTION_ORIGINAL_IMAGE'); ?></h5>
                </div>
                <div class="card-body text-center p-2">
                    <img src="<?php echo JURI::root() . $this->result['original_image']; ?>" 
                         alt="Original Image" class="result-image img-fluid">
                </div>
            </div>
        </div>
        
        <?php if ($this->result['result_image']): ?>
        <div class="col-md-6">
            <div class="card border-light">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo JText::_('COM_OBJECTDETECTION_PROCESSED_IMAGE'); ?></h5>
                </div>
                <div class="card-body text-center p-2">
                    <img src="<?php echo JURI::root() . $this->result['result_image']; ?>" 
                         alt="Processed Image" class="result-image img-fluid">
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="detection-info card border-light mt-3">
        <div class="card-body py-2">
            <div class="row text-small">
                <div class="col-md-4">
                    <strong><?php echo JText::_('COM_OBJECTDETECTION_FILE'); ?>:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($this->result['original_filename']); ?></span>
                </div>
                <div class="col-md-4">
                    <strong><?php echo JText::_('COM_OBJECTDETECTION_CONFIDENCE'); ?>:</strong><br>
                    <span class="text-muted"><?php echo $this->result['confidence']; ?></span>
                </div>
                <div class="col-md-4">
                    <strong><?php echo JText::_('COM_OBJECTDETECTION_PROCESSED'); ?>:</strong><br>
                    <span class="text-muted"><?php echo JHtml::_('date', $this->result['timestamp'], 'd.m.Y H:i:s'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="detections-list card border-light mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo JText::_('COM_OBJECTDETECTION_DETECTED_OBJECTS'); ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->result['detections'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th class="border-0"><?php echo JText::_('COM_OBJECTDETECTION_OBJECT'); ?></th>
                                <th class="border-0"><?php echo JText::_('COM_OBJECTDETECTION_CONFIDENCE_VALUE'); ?></th>
                                <th class="border-0"><?php echo JText::_('COM_OBJECTDETECTION_COORDINATES'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->result['detections'] as $detection): ?>
                            <tr>
                                <td class="align-middle"><?php echo htmlspecialchars($detection['label']); ?></td>
                                <td class="align-middle">
                                    <span class="badge bg-<?php echo $detection['confidence'] > 0.7 ? 'success' : ($detection['confidence'] > 0.4 ? 'warning' : 'danger'); ?>">
                                        <?php echo number_format($detection['confidence'], 3); ?>
                                    </span>
                                </td>
                                <td class="align-middle coordinates-cell">
                                    (<?php echo (int)$detection['bbox'][0]; ?>, <?php echo (int)$detection['bbox'][1]; ?>) - 
                                    (<?php echo (int)$detection['bbox'][2]; ?>, <?php echo (int)$detection['bbox'][3]; ?>)
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    <h6 class="alert-heading"><?php echo JText::_('COM_OBJECTDETECTION_NO_OBJECTS'); ?></h6>
                    <p class="mb-0 text-small"><?php echo JText::_('COM_OBJECTDETECTION_TRY_LOWERING'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="action-buttons mt-3 text-center">
        <a href="<?php echo JRoute::_('index.php?option=com_objectdetection'); ?>" class="btn btn-primary">
            <span class="icon-loop" aria-hidden="true"></span>
            <?php echo JText::_('COM_OBJECTDETECTION_DETECT_ANOTHER'); ?>
        </a>
    </div>

    <!-- Lightbox для просмотра изображений -->
    <div id="lightbox" class="lightbox">
        <button class="lightbox-close" aria-label="<?php echo JText::_('JCLOSE'); ?>">
            <span class="icon-remove" aria-hidden="true"></span>
        </button>
        <button class="lightbox-nav lightbox-prev" aria-label="<?php echo JText::_('JPREVIOUS'); ?>">
            <span class="icon-chevron-left" aria-hidden="true"></span>
        </button>
        <div class="lightbox-content">
            <img class="lightbox-img" src="" alt="">
            <a class="lightbox-download" href="#" download>
                <span class="icon-download" aria-hidden="true"></span>
                <?php echo JText::_('COM_OBJECTDETECTION_DOWNLOAD_IMAGE'); ?>
            </a>
        </div>
        <button class="lightbox-nav lightbox-next" aria-label="<?php echo JText::_('JNEXT'); ?>">
            <span class="icon-chevron-right" aria-hidden="true"></span>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = lightbox.querySelector('.lightbox-img');
    const lightboxDownload = lightbox.querySelector('.lightbox-download');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');
    
    const images = [
        '<?php echo JURI::root() . $this->result['original_image']; ?>',
        <?php if ($this->result['result_image']): ?>
        '<?php echo JURI::root() . $this->result['result_image']; ?>'
        <?php endif; ?>
    ].filter(Boolean);
    
    let currentImageIndex = 0;

    // Функция для переключения на предыдущее изображение
    function showPreviousImage() {
        if (images.length > 1) {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            updateLightbox();
        }
    }

    // Функция для переключения на следующее изображение
    function showNextImage() {
        if (images.length > 1) {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            updateLightbox();
        }
    }

    // Функция для закрытия lightbox
    function closeLightbox() {
        lightbox.classList.remove('active');
        // Удаляем обработчик клавиатуры при закрытии
        document.removeEventListener('keydown', handleKeyPress);
    }

    // Обработчик нажатий клавиш
    function handleKeyPress(e) {
        // Только если lightbox открыт
        if (!lightbox.classList.contains('active')) return;

        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                showPreviousImage();
                break;
            case 'ArrowRight':
                e.preventDefault();
                showNextImage();
                break;
            case 'Escape':
                e.preventDefault();
                closeLightbox();
                break;
        }
    }

    // Открытие lightbox
    document.querySelectorAll('.result-image').forEach((img, index) => {
        img.addEventListener('click', function() {
            currentImageIndex = index;
            updateLightbox();
            lightbox.classList.add('active');
            // Добавляем обработчик клавиатуры при открытии
            document.addEventListener('keydown', handleKeyPress);
        });
    });
    
    // Закрытие lightbox
    closeBtn.addEventListener('click', function() {
        closeLightbox();
    });
    
    // Навигация кнопками
    prevBtn.addEventListener('click', showPreviousImage);
    nextBtn.addEventListener('click', showNextImage);
    
    // Закрытие по клику на фон
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
    
    function updateLightbox() {
        lightboxImg.src = images[currentImageIndex];
        lightboxDownload.href = images[currentImageIndex];
        
        // ИСПРАВЛЕННАЯ ЧАСТЬ С ПЕРЕВОДАМИ:
        lightboxDownload.textContent = '<?php echo JText::_("COM_OBJECTDETECTION_DOWNLOAD_IMAGE"); ?>';
        lightboxDownload.download = 'detection_' + (currentImageIndex === 0 ? 
            '<?php echo JText::_("COM_OBJECTDETECTION_ORIGINAL_IMAGE"); ?>' : 
            '<?php echo JText::_("COM_OBJECTDETECTION_PROCESSED_IMAGE"); ?>') + '.jpg';
        
        // Скрываем кнопки навигации если только одно изображение
        if (images.length <= 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }
    }

    // Закрытие lightbox при изменении ориентации устройства (для мобильных)
    window.addEventListener('orientationchange', function() {
        if (lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });
});
</script>