/**
 * Общие скрипты для приложения
 */

/**
 * Инициализация drag & drop для файлов (блоги, записи и т.д.)
 */
function initFilesUpload() {
    // Находим ВСЕ зоны загрузки на странице
    const dropZones = document.querySelectorAll('.file-upload-area');

    dropZones.forEach(function(dropZone) {
        // Ищем элементы относительно текущей зоны
        const container = dropZone.closest('.mb-3') || dropZone.parentElement;
        const fileInput = container.querySelector('input[type="file"][name*="attachments"]');
        const chooseFilesBtn = dropZone.querySelector('.btn-secondary, #chooseFilesBtn');
        const fileListDiv = dropZone.querySelector('.file-list, #fileList');

        if (!fileInput || !chooseFilesBtn || !fileListDiv) {
            console.log('Элементы не найдены для зоны загрузки');
            return;
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
            // Игнорируем клики по кнопке и элементам удаления
            if (e.target.closest('.btn-secondary') ||
                e.target.closest('#chooseFilesBtn') ||
                e.target.closest('.file-item-remove')) {
                return;
            }
            fileInput.click();
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
        return;
    }

    chooseFileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileInput.click();
    });

    fileInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files.length > 0) {
            handleFiles(e.target.files);
        }
    });

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
        if (files.length === 0) return;

        const file = files[0];

        // Проверка типа
        if (!file.type.match('image.*')) {
            alert('Пожалуйста, выберите изображение');
            return;
        }

        // Проверка размера (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Файл слишком большой. Максимум 5 МБ');
            return;
        }

        // Показываем превью
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';

            if (currentAvatar) {
                currentAvatar.style.display = 'none';
            }

            if (fileInfo) {
                const sizeKB = (file.size / 1024).toFixed(2);
                fileInfo.textContent = `Выбран: ${file.name} (${sizeKB} KB)`;
                fileInfo.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Обновление счётчика уведомлений
 */
function updateNotificationCount() {
    fetch('/api/notifications/count')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notification-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

/**
 * Инициализация страницы блога (если есть)
 */
function initBlogShow() {
    if (typeof window.blogShowData === 'undefined') {
        return;
    }

    const addPostBtn = document.getElementById('add-post-btn');
    const openFileUploadBtn = document.querySelector('.open-file-upload');
    const fileInput = document.getElementById('post-file-input');

    if (addPostBtn) {
        addPostBtn.addEventListener('click', submitPost);
    }

    if (openFileUploadBtn && fileInput) {
        openFileUploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', updateSelectedFiles);
    }

    // Копирование ссылок
    document.querySelectorAll('.copy-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            copyToClipboard(url);
        });
    });
}

/**
 * Обновление списка выбранных файлов
 */
function updateSelectedFiles() {
    const fileInput = document.getElementById('post-file-input');
    const filesDiv = document.getElementById('selected-files');
    const fileCount = document.getElementById('file-count');

    if (!fileInput || !filesDiv || !fileCount) {
        return;
    }

    if (fileInput.files.length > 0) {
        filesDiv.style.display = 'block';
        fileCount.textContent = fileInput.files.length;
        toggleSendButton();
    } else {
        filesDiv.style.display = 'none';
    }
}

function toggleSendButton() {
    const input = document.getElementById('post-content-input');
    const fileInput = document.getElementById('post-file-input');
    const sendBtn = document.getElementById('add-post-btn');

    if (!input || !fileInput || !sendBtn) {
        return;
    }

    const hasContent = input.value.trim().length > 0;
    const hasFiles = fileInput.files.length > 0;

    sendBtn.disabled = !hasContent && !hasFiles;
}

/**
 * Отправка новой записи через AJAX
 */
async function submitPost() {
    const input = document.getElementById('post-content-input');
    const fileInput = document.getElementById('post-file-input');
    const blogId = window.blogShowData.blogId;

    if (!input || !fileInput) {
        return;
    }

    const content = input.value.trim();

    if (!content && fileInput.files.length === 0) {
        return;
    }

    const formData = new FormData();
    formData.append('content', content);
    formData.append('title', content.substring(0, 50) || 'Без заголовка');

    for (let file of fileInput.files) {
        formData.append('attachments[]', file);
    }

    const url = `/post/blog/${blogId}/new/ajax`;

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            addPostToList(data.post);

            input.value = '';
            fileInput.value = '';
            document.getElementById('selected-files').style.display = 'none';
            toggleSendButton();
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось добавить запись'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ошибка при добавлении записи');
    }
}

/**
 * Добавить новую запись в список
 */
function addPostToList(post) {
    const container = document.getElementById('posts-container');

    if (!container) {
        return;
    }

    // Убираем сообщение "Записей пока нет"
    const emptyMessage = container.querySelector('p.text-muted');
    if (emptyMessage) {
        emptyMessage.remove();
    }

    const avatarHtml = post.author.avatar ?
        `<img src="/uploads/avatars/${post.author.avatar}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">` :
        `<i class="bi bi-person-circle fs-4"></i>`;

    const editMenuHtml = post.canEdit ?
        `<li>
            <a class="dropdown-item" href="/post/${post.id}/edit">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </li>` : '';

    // Генерируем HTML для файлов
    let attachmentsHtml = '';
    if (post.attachments && post.attachments.length > 0) {
        attachmentsHtml = `
            <div class="mt-3">
                <h6 class="text-muted mb-2">Файлы:</h6>
                ${post.attachments.map(att => `
                    <div class="d-inline-block me-2 mb-2">
                        <a href="${att.url}" 
                           class="btn btn-sm btn-outline-secondary" 
                           target="_blank">
                            📎 ${escapeHtml(att.originalFilename)}
                        </a>
                    </div>
                `).join('')}
            </div>
        `;
    }

    const postHtml = `
        <div class="card mb-3 post-item" data-post-id="${post.id}">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <div class="d-flex align-items-center gap-2">
                    ${avatarHtml}
                    <strong>${escapeHtml(post.author.username)}</strong>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">${post.createdAt}</span>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            ${editMenuHtml}
                            <li>
                                <a class="dropdown-item copy-link" href="#" data-url="${post.url}">
                                    <i class="bi bi-link-45deg"></i> Скопировать ссылку
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title">${escapeHtml(post.title)}</h5>
                <p class="card-text">${escapeHtml(post.content).replace(/\n/g, '<br>')}</p>
                ${attachmentsHtml}
            </div>
        </div>
    `;

    // Добавляем в конец списка (новые записи внизу)
    container.insertAdjacentHTML('beforeend', postHtml);

    // Добавляем обработчик для копирования ссылки
    const newPost = container.lastElementChild;
    const copyLink = newPost.querySelector('.copy-link');
    if (copyLink) {
        copyLink.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            copyToClipboard(url);
        });
    }
}

/**
 * Копирование в буфер обмена
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showCopyToast();
    } catch (err) {
        console.error('Failed to copy:', err);
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showCopyToast();
        } catch (err) {
            console.error('Fallback copy failed:', err);
        }
        document.body.removeChild(textArea);
    }
}

/**
 * Показать toast "Скопировано!"
 */
function showCopyToast() {
    const toastEl = document.getElementById('copyToast');
    if (toastEl && typeof bootstrap !== 'undefined') {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
}

/**
 * Экранирование HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * Инициализация формы регистрации пользователя
 */
function initRegistrationForm() {
    const registrationForm = document.getElementById('registration-form');
    
    if (!registrationForm) {
        return; // Не на странице регистрации
    }

    const modal = new bootstrap.Modal(document.getElementById('newDepartmentModal'));
    const customSelect = $('#custom-department-select');
    const hiddenInput = $('#registration_form_departmentId');
    const newDepartmentInput = $('#new-department-name');
    const departmentError = $('#department-error');
    const createBtn = $('#create-department-btn');

    // Загружаем список подразделений
    function loadDepartments(selectedId) {
        $.ajax({
            url: window.departmentsApiUrl,
            method: 'GET',
            success: function(departments) {
                // Очищаем, кроме первых двух опций
                customSelect.find('option').slice(2).remove();
                
                // Добавляем подразделения
                departments.forEach(function(dept) {
                    const option = $('<option></option>')
                        .attr('value', dept.id)
                        .text(dept.text);
                    customSelect.append(option);
                });
                
                // Выбираем нужное
                if (selectedId) {
                    customSelect.val(selectedId);
                    hiddenInput.val(selectedId);
                }
            }
        });
    }

    // Загружаем при инициализации
    loadDepartments();

    // Синхронизируем кастомный select со скрытым полем
    customSelect.on('change', function() {
        const value = $(this).val();
        
        if (value === '__new__') {
            modal.show();
            newDepartmentInput.val('').removeClass('is-invalid');
            departmentError.text('');
        } else {
            hiddenInput.val(value);
        }
    });

    // Создание подразделения
    createBtn.on('click', function() {
        const name = newDepartmentInput.val().trim();
        
        if (!name) {
            newDepartmentInput.addClass('is-invalid');
            departmentError.text('Название не может быть пустым');
            return;
        }

        createBtn.prop('disabled', true).text('Создание...');

        $.ajax({
            url: window.departmentsCreateApiUrl,
            method: 'POST',
            data: { name: name },
            success: function(response) {
                if (response.success) {
                    // Перезагружаем список и выбираем новое
                    loadDepartments(response.department.id);
                    
                    // Закрываем модалку
                    modal.hide();
                    
                    // Показываем уведомление
                    const alertDiv = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        'Подразделение "' + response.department.name + '" создано!' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.card-body').prepend(alertDiv);
                    
                    setTimeout(function() {
                        alertDiv.fadeOut(function() { $(this).remove(); });
                    }, 3000);
                } else {
                    newDepartmentInput.addClass('is-invalid');
                    departmentError.text(response.error);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                newDepartmentInput.addClass('is-invalid');
                departmentError.text(response && response.error ? response.error : 'Ошибка сервера');
            },
            complete: function() {
                createBtn.prop('disabled', false).text('Создать');
            }
        });
    });

    // Сброс при закрытии модалки
    $('#newDepartmentModal').on('hidden.bs.modal', function() {
        if (customSelect.val() === '__new__') {
            customSelect.val('');
            hiddenInput.val('');
        }
    });

    // Убираем ошибку при вводе
    newDepartmentInput.on('input', function() {
        $(this).removeClass('is-invalid');
        departmentError.text('');
    });

    // Проверка перед отправкой
    registrationForm.addEventListener('submit', function(e) {
        const value = hiddenInput.val();
        
        if (!value || value === '__new__') {
            e.preventDefault();
            alert('Пожалуйста, выберите подразделение');
            return false;
        }
    });
}

/**
 * Инициализация всех скриптов при загрузке страницы
 */
window.addEventListener('load', function() {
    // Инициализируем загрузку файлов (если есть на странице)
    initFilesUpload();

    // Инициализируем загрузку аватара (если есть на странице)
    initAvatarUpload();

    // Инициализируем Select2 для множественного выбора участников
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('.participants-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return jQuery(this).data('placeholder');
            },
            allowClear: true,
            closeOnSelect: false
        });
    }

    // Обновляем уведомления
    updateNotificationCount();

    // Обновляем каждые 30 секунд
    setInterval(updateNotificationCount, 30000);

    // Инициализируем страницу блога
    initBlogShow();
    
    // Инициализируем форму регистрации
    initRegistrationForm();
});
