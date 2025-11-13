// media/js/objectdetection.js

document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('preview');
    const previewImage = document.getElementById('preview-image');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const removeBtn = document.getElementById('remove-file');
    const detectBtn = document.getElementById('detect-btn');
    const browseBtn = dropArea.querySelector('.browse-btn');
    const confidenceSlider = document.getElementById('confidence-slider');
    const confidenceValue = document.getElementById('confidence-value');

    // Function to get translation
    function getTranslation(key) {
        const element = document.querySelector(`[data-string="${key}"]`);
        return element ? element.textContent : key;
    }

    // Update confidence value
    if (confidenceSlider && confidenceValue) {
        confidenceSlider.addEventListener('input', function(e) {
            confidenceValue.textContent = e.target.value;
        });
    }

    // Hide default file input
    fileInput.style.display = 'none';

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);

    // Click to select files
    browseBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.click();
    });

    dropArea.addEventListener('click', function(e) {
        if (!e.target.closest('.remove-btn') && !e.target.closest('.browse-btn')) {
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    removeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        resetFileInput();
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropArea.classList.add('highlight');
    }

    function unhighlight() {
        dropArea.classList.remove('highlight');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            if (!file.type.match('image.*')) {
                alert(getTranslation('COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE_FILE'));
                return;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                previewContainer.style.display = 'block';
                detectBtn.disabled = false;
                dropArea.querySelector('.drop-zone__prompt').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }

    function resetFileInput() {
        fileInput.value = '';
        previewContainer.style.display = 'none';
        detectBtn.disabled = true;
        dropArea.querySelector('.drop-zone__prompt').style.display = 'flex';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form validation
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert(getTranslation('COM_OBJECTDETECTION_PLEASE_SELECT_IMAGE'));
            return false;
        }
        
        detectBtn.innerHTML = '<span class="icon-spinner spinner" aria-hidden="true"></span> ' + 
                             getTranslation('COM_OBJECTDETECTION_PROCESSING');
        detectBtn.disabled = true;
    });
});