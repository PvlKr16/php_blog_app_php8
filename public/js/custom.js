/**
 * Общие скрипты для приложения
 */

/**
 * Инициализация drag & drop для файлов (блоги и комментарии)
 */
function initFilesUpload() {
    // Находим ВСЕ зоны загрузки на странице (может быть несколько форм комментариев)
    const dropZones = document.querySelectorAll('.file-upload-area');

    dropZones.forEach(function(dropZone) {
        const fileInput = dropZone.parentElement.querySelector('input[type="file"][name*="attachments"]');
        const chooseFilesBtn = dropZone.querySelector('.chooseFilesBtn');
        const fileListDiv = dropZone.querySelector('.fileList');

        if (!fileInput || !chooseFilesBtn || !fileListDiv) {
            return; // Элементы не найдены
        }

        let selectedFiles = new DataTransfer();

        // Кнопка "Выбрать файлы"
        chooseFilesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });

        // Клик по зоне
        dropZone.addEventListener('click', function(e) {
            if (!e.target.closest('.chooseFilesBtn') && !e.target.closest('.file-item-remove')) {
                fileInput.click();
            }
        });

        // Изменение файла через input
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                addFiles(e.target.files);
            }
        });

        // Drag & Drop события
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        dropZone.addEventListener('dragenter', function(e) {
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragover', function(e) {
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', function(e) {
            if (e.target === dropZone) {
                dropZone.classList.remove('dragover');
            }
        });

        dropZone.addEventListener('drop', function(e) {
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                addFiles(files);
            }
        });

        function addFiles(files) {
            // Проверка количества файлов
            if (selectedFiles.files.length + files.length > 5) {
                alert('Можно загрузить максимум 5 файлов');
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Проверка типа файла
                const allowedTypes = [
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                    'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg',
                    'application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain', 'text/markdown'
                ];

                if (!allowedTypes.includes(file.type)) {
                    alert('Файл "' + file.name + '" имеет недопустимый тип');
                    continue;
                }

                // Проверка размера (20MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert('Файл "' + file.name + '" слишком большой. Максимум 20 МБ');
                    continue;
                }

                selectedFiles.items.add(file);
            }

            updateFileInput();
            displayFiles();
        }

        function updateFileInput() {
            fileInput.files = selectedFiles.files;
        }

        function displayFiles() {
            fileListDiv.innerHTML = '';

            if (selectedFiles.files.length === 0) {
                return;
            }

            for (let i = 0; i < selectedFiles.files.length; i++) {
                const file = selectedFiles.files[i];
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';

                const fileSizeKB = (file.size / 1024).toFixed(2);
                const icon = getFileIcon(file.type);

                fileItem.innerHTML = `
                    <div class="file-item-info">
                        <span style="font-size: 20px;">${icon}</span>
                        <strong>${file.name}</strong>
                        <span class="text-muted">(${fileSizeKB} KB)</span>
                    </div>
                    <span class="file-item-remove" data-index="${i}">✕</span>
                `;

                fileListDiv.appendChild(fileItem);
            }

            // Обработчики удаления
            fileListDiv.querySelectorAll('.file-item-remove').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFile(index);
                });
            });
        }

        function removeFile(index) {
            const newFileList = new DataTransfer();

            for (let i = 0; i < selectedFiles.files.length; i++) {
                if (i !== index) {
                    newFileList.items.add(selectedFiles.files[i]);
                }
            }

            selectedFiles = newFileList;
            updateFileInput();
            displayFiles();
        }

        function getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) return '🖼️';
            if (mimeType.startsWith('audio/')) return '🎵';
            if (mimeType.includes('pdf') || mimeType.includes('word') || mimeType.includes('text')) return '📄';
            return '📎';
        }
    });
}

/**
 * Инициализация drag & drop для аватара
 */
function initAvatarUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.querySelector('input[type="file"][name*="avatar"]');
    const chooseFileBtn = document.getElementById('chooseFileBtn');
    const preview = document.getElementById('avatarPreview');
    const fileInfo = document.getElementById('fileInfo');
    const currentAvatar = document.getElementById('currentAvatar');

    if (!dropZone || !fileInput || !chooseFileBtn) {
        return; // Элементы не найдены на странице
    }

    // Кнопка "Выбрать файл"
    chooseFileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileInput.click();
    });

    // Изменение файла через input
    fileInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files.length > 0) {
            handleFiles(e.target.files);
        }
    });

    // Drag & Drop события - ТОЛЬКО на dropZone
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    dropZone.addEventListener('dragenter', function(e) {
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragover', function(e) {
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', function(e) {
        if (e.target === dropZone) {
            dropZone.classList.remove('dragover');
        }
    });

    dropZone.addEventListener('drop', function(e) {
        dropZone.classList.remove('dragover');

        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            fileInput.files = dataTransfer.files;

            handleFiles(files);
        }
    });

    function handleFiles(files) {
        if (files.length === 0) {
            return;
        }

        const file = files[0];

        // Проверка типа файла
        if (!file.type.match('image.*')) {
            alert('Пожалуйста, выберите изображение');
            fileInput.value = '';
            return;
        }

        // Проверка размера (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Файл слишком большой. Максимум 5 МБ');
            fileInput.value = '';
            return;
        }

        // Показываем информацию о файле
        const fileSizeKB = (file.size / 1024).toFixed(2);
        if (fileInfo) {
            fileInfo.innerHTML = '<strong>' + file.name + '</strong><br>' + fileSizeKB + ' КБ';
        }

        // Скрываем текущий аватар, если есть
        if (currentAvatar) {
            currentAvatar.style.display = 'none';
        }

        // Показываем превью нового
        if (preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.onerror = function(e) {
                console.error('Ошибка при чтении файла:', e);
            };
            reader.readAsDataURL(file);
        }
    }
}

/**
 * Переключение формы ответа на комментарий
 */
function toggleReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

/**
 * Инициализация всех скриптов при загрузке страницы
 */
window.addEventListener('load', function() {
    // Инициализируем загрузку файлов (если есть на странице)
    initFilesUpload();

    // Инициализируем загрузку аватара (если есть на странице)
    initAvatarUpload();
});