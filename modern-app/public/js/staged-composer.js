document.addEventListener('DOMContentLoaded', () => {
    const translate = (template, replacements = {}) => Object.entries(replacements).reduce(
        (message, [token, value]) => message.replaceAll(token, String(value)),
        template,
    );

    const formatSize = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '';
        }

        if (bytes < 1024 * 1024) {
            return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const formatDimensions = (width, height) => {
        if (!Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0) {
            return '';
        }

        return `${width}x${height}`;
    };

    const fileKey = (file) => [file.name, file.size, file.lastModified].join(':');

    const outputTypeFor = (file) => {
        switch (file.type) {
            case 'image/jpeg':
            case 'image/png':
            case 'image/webp':
                return file.type;
            case 'image/bmp':
                return 'image/png';
            default:
                return null;
        }
    };

    const extensionForType = (type, fallbackName) => {
        switch (type) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            default:
                return (fallbackName.split('.').pop() || 'png').toLowerCase();
        }
    };

    const renameForType = (name, type) => {
        const base = name.replace(/\.[^.]+$/, '');

        return `${base}.${extensionForType(type, name)}`;
    };

    const blobToFile = (blob, name, type) => new File([blob], renameForType(name, type), {
        type,
        lastModified: Date.now(),
    });

    const inferExtensionFromType = (type) => {
        switch (type) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            case 'image/gif':
                return 'gif';
            case 'image/bmp':
                return 'bmp';
            default:
                return 'png';
        }
    };

    const normalizeIncomingFile = (file) => {
        if (!(file instanceof File)) {
            return null;
        }

        if (file.name && file.name.trim() !== '') {
            return file;
        }

        const extension = inferExtensionFromType(file.type);
        const generatedName = `pasted-image-${Date.now()}-${Math.random().toString(36).slice(2, 8)}.${extension}`;

        return new File([file], generatedName, {
            type: file.type,
            lastModified: Date.now(),
        });
    };

    const loadImage = (file) => new Promise((resolve, reject) => {
        const imageUrl = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => resolve({
            image,
            width: image.naturalWidth || image.width,
            height: image.naturalHeight || image.height,
            imageUrl,
        });

        image.onerror = () => {
            URL.revokeObjectURL(imageUrl);
            reject(new Error(`Unable to read ${file.name}.`));
        };

        image.src = imageUrl;
    });

    const canvasToBlob = (canvas, type, quality) => new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), type, quality);
    });

    const prepareImageFile = async (file, maxWidth, maxHeight, quality, text = {}) => {
        const outputType = outputTypeFor(file);

        if (!outputType) {
            return {
                file,
                key: fileKey(file),
                note: file.type === 'image/gif' ? (text.gifOriginal ?? 'GIF kept original to avoid breaking animation.') : null,
                finalWidth: null,
                finalHeight: null,
            };
        }

        const { image, width, height, imageUrl } = await loadImage(file);

        try {
            if (width <= maxWidth && height <= maxHeight) {
                return {
                    file,
                    key: fileKey(file),
                    note: null,
                    finalWidth: width,
                    finalHeight: height,
                };
            }

            const scale = Math.min(maxWidth / width, maxHeight / height);
            const targetWidth = Math.max(1, Math.round(width * scale));
            const targetHeight = Math.max(1, Math.round(height * scale));
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            if (!context) {
                return {
                    file,
                    key: fileKey(file),
                    note: text.browserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
                    finalWidth: width,
                    finalHeight: height,
                };
            }

            canvas.width = targetWidth;
            canvas.height = targetHeight;
            context.drawImage(image, 0, 0, targetWidth, targetHeight);

            const blob = await canvasToBlob(canvas, outputType, quality);

            if (!(blob instanceof Blob)) {
                return {
                    file,
                    key: fileKey(file),
                    note: text.browserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
                    finalWidth: width,
                    finalHeight: height,
                };
            }

            return {
                file: blobToFile(blob, file.name, outputType),
                key: fileKey(file),
                note: translate(
                    text.resized ?? 'Resized from __FROM__ to __TO__.',
                    {
                        '__FROM__': formatDimensions(width, height),
                        '__TO__': formatDimensions(targetWidth, targetHeight),
                    },
                ),
                finalWidth: targetWidth,
                finalHeight: targetHeight,
            };
        } finally {
            URL.revokeObjectURL(imageUrl);
        }
    };

    const extractErrorMessage = (payload) => {
        if (!payload) {
            return text.uploadFailed ?? 'Upload failed.';
        }

        if (typeof payload.message === 'string' && payload.message !== '') {
            return payload.message;
        }

        if (payload.errors && typeof payload.errors === 'object') {
            const firstField = Object.keys(payload.errors)[0];
            const messages = payload.errors[firstField];

            if (Array.isArray(messages) && messages.length > 0) {
                return String(messages[0]);
            }
        }

        return text.uploadFailed ?? 'Upload failed.';
    };

    document.querySelectorAll('[data-staged-uploader]').forEach((uploader) => {
        const form = uploader.closest('form');
        const input = uploader.querySelector('[data-staged-input]');
        const target = uploader.querySelector('[data-staged-dropzone-target]');
        const trigger = uploader.querySelector('[data-staged-trigger]');
        const list = uploader.querySelector('[data-staged-list]');
        const summary = uploader.querySelector('[data-staged-summary]');
        const hiddenInputs = uploader.querySelector('[data-staged-hidden-inputs]');
        const uploadUrl = uploader.dataset.stagedUploadUrl ?? '';
        const removeUrlBase = uploader.dataset.stagedRemoveUrlBase ?? '';
        const hiddenInputName = uploader.dataset.stagedHiddenInputName ?? 'staged_uploads[]';
        const maxFiles = Number.parseInt(uploader.dataset.stagedMaxFiles ?? '12', 10) || 12;
        const maxWidth = Number.parseInt(uploader.dataset.stagedMaxWidth ?? '1920', 10) || 1920;
        const maxHeight = Number.parseInt(uploader.dataset.stagedMaxHeight ?? '1080', 10) || 1080;
        const resizeQuality = Number.parseFloat(uploader.dataset.stagedResizeQuality ?? '0.9') || 0.9;
        const text = {
            noImagesUploaded: uploader.dataset.textNoImagesUploaded ?? 'No images uploaded yet.',
            uploadedSummary: uploader.dataset.textUploadedSummary ?? '__COUNT__ uploaded',
            inProgressSummary: uploader.dataset.textInProgressSummary ?? '__COUNT__ in progress',
            failedSummary: uploader.dataset.textFailedSummary ?? '__COUNT__ failed',
            uploading: uploader.dataset.textUploading ?? 'Uploading __PERCENT__%',
            uploadedReady: uploader.dataset.textUploadedReady ?? 'Uploaded and ready to post.',
            preparingImage: uploader.dataset.textPreparingImage ?? 'Preparing image...',
            waitingUpload: uploader.dataset.textWaitingUpload ?? 'Waiting to upload...',
            cancel: uploader.dataset.textCancel ?? 'Cancel',
            remove: uploader.dataset.textRemove ?? 'Remove',
            networkError: uploader.dataset.textNetworkError ?? 'Network error while uploading image.',
            uploadFailed: uploader.dataset.textUploadFailed ?? 'Upload failed.',
            pleaseWait: uploader.dataset.textPleaseWait ?? 'Please wait for image uploads to finish before posting.',
            gifOriginal: uploader.dataset.textGifOriginal ?? 'GIF kept original to avoid breaking animation.',
            browserResizeUnavailable: uploader.dataset.textBrowserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
            resized: uploader.dataset.textResized ?? 'Resized from __FROM__ to __TO__.',
        };
        const csrfToken = form?.querySelector('input[name="_token"]')?.value ?? '';
        const submitButtons = form ? Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]')) : [];

        if (!form || !input || !target || !trigger || !list || !summary || !hiddenInputs || uploadUrl === '') {
            return;
        }

        const items = [];
        let queue = Promise.resolve();

        const uploadedItems = () => items.filter((item) => item.status === 'uploaded' && item.token);
        const busyItems = () => items.filter((item) => ['queued', 'preparing', 'uploading'].includes(item.status));

        const syncHiddenInputs = () => {
            hiddenInputs.innerHTML = '';

            uploadedItems().forEach((item) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = hiddenInputName;
                hidden.value = item.token;
                hiddenInputs.append(hidden);
            });
        };

        const updateSubmitState = () => {
            const busy = busyItems().length > 0;

            submitButtons.forEach((button) => {
                button.disabled = busy;
            });
        };

        const renderSummary = () => {
            if (items.length === 0) {
                summary.textContent = text.noImagesUploaded;
                return;
            }

            const parts = [];

            if (uploadedItems().length > 0) {
                parts.push(translate(text.uploadedSummary, { '__COUNT__': uploadedItems().length }));
            }

            if (busyItems().length > 0) {
                parts.push(translate(text.inProgressSummary, { '__COUNT__': busyItems().length }));
            }

            const errorCount = items.filter((item) => item.status === 'error').length;
            if (errorCount > 0) {
                parts.push(translate(text.failedSummary, { '__COUNT__': errorCount }));
            }

            summary.textContent = parts.join(' | ');
        };

        const render = () => {
            syncHiddenInputs();
            updateSubmitState();
            renderSummary();

            if (items.length === 0) {
                list.hidden = true;
                list.innerHTML = '';
                target.classList.remove('legacy-uploader__dropzone--has-files');
                return;
            }

            list.hidden = false;
            list.innerHTML = '';
            target.classList.add('legacy-uploader__dropzone--has-files');

            items.forEach((item, index) => {
                const listItem = document.createElement('li');
                listItem.className = `legacy-uploader__item legacy-uploader__item--${item.status}`;

                const meta = document.createElement('div');
                meta.className = 'legacy-uploader__item-meta';

                const nameLine = document.createElement('strong');
                nameLine.textContent = `${index + 1}. ${item.file.name}`;
                meta.append(nameLine);

                const detailLine = document.createElement('span');
                detailLine.textContent = [
                    formatSize(item.file.size),
                    item.finalWidth && item.finalHeight ? `${formatDimensions(item.finalWidth, item.finalHeight)}px` : '',
                ].filter(Boolean).join(' | ');
                if (detailLine.textContent !== '') {
                    meta.append(detailLine);
                }

                const statusLine = document.createElement('small');
                statusLine.className = 'legacy-uploader__status';
                statusLine.textContent = item.error
                    ? item.error
                    : item.status === 'uploading'
                        ? translate(text.uploading, { '__PERCENT__': Math.round(item.progress) })
                        : item.status === 'uploaded'
                            ? text.uploadedReady
                            : item.status === 'preparing'
                                ? text.preparingImage
                                : text.waitingUpload;
                meta.append(statusLine);

                if (item.note) {
                    const noteLine = document.createElement('small');
                    noteLine.textContent = item.note;
                    meta.append(noteLine);
                }

                const actions = document.createElement('div');
                actions.className = 'legacy-uploader__item-actions';

                if (item.status === 'uploading') {
                    const progress = document.createElement('div');
                    progress.className = 'legacy-uploader__progress';

                    const bar = document.createElement('div');
                    bar.className = 'legacy-uploader__progress-bar';
                    bar.style.width = `${Math.max(5, item.progress)}%`;
                    progress.append(bar);
                    actions.append(progress);
                }

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'legacy-uploader__remove';
                remove.textContent = item.status === 'uploading' ? text.cancel : text.remove;
                remove.addEventListener('click', () => {
                    void removeItem(item);
                });
                actions.append(remove);

                listItem.append(meta, actions);
                list.append(listItem);
            });
        };

        const removeItem = async (item) => {
            item.removed = true;

            if (item.xhr && item.status === 'uploading') {
                item.xhr.abort();
            }

            if (item.token) {
                try {
                    await fetch(`${removeUrlBase}/${encodeURIComponent(item.token)}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                } catch (error) {
                    // Ignore cleanup failures.
                }
            }

            const index = items.indexOf(item);
            if (index >= 0) {
                items.splice(index, 1);
            }

            render();
        };

        const uploadPreparedItem = (item) => new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            item.xhr = xhr;
            item.status = 'uploading';
            item.progress = 0;
            item.error = null;
            render();

            xhr.open('POST', uploadUrl);
            xhr.responseType = 'json';
            xhr.setRequestHeader('Accept', 'application/json');

            if (csrfToken !== '') {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            }

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }

                item.progress = (event.loaded / event.total) * 100;
                render();
            });

            xhr.addEventListener('load', () => {
                item.xhr = null;

                if (item.removed) {
                    resolve();
                    return;
                }

                const payload = xhr.response ?? (() => {
                    try {
                        return JSON.parse(xhr.responseText);
                    } catch (error) {
                        return null;
                    }
                })();

                if (xhr.status >= 200 && xhr.status < 300 && payload) {
                    item.status = 'uploaded';
                    item.progress = 100;
                    item.token = payload.token;
                    item.finalWidth = payload.width ?? item.finalWidth;
                    item.finalHeight = payload.height ?? item.finalHeight;
                    render();
                    resolve();
                    return;
                }

                item.status = 'error';
                item.error = extractErrorMessage(payload, text);
                render();
                reject(new Error(item.error));
            });

            xhr.addEventListener('error', () => {
                item.xhr = null;
                item.status = 'error';
                item.error = text.networkError;
                render();
                reject(new Error(item.error));
            });

            xhr.addEventListener('abort', () => {
                item.xhr = null;
                resolve();
            });

            const formData = new FormData();
            formData.append('image', item.file, item.file.name);
            xhr.send(formData);
        });

        const queueUpload = (item) => {
            queue = queue
                .then(async () => {
                    if (item.removed) {
                        return;
                    }

                    item.status = 'preparing';
                    render();

                    const prepared = await prepareImageFile(item.file, maxWidth, maxHeight, resizeQuality, text);

                    if (item.removed) {
                        return;
                    }

                    item.file = prepared.file;
                    item.note = prepared.note;
                    item.finalWidth = prepared.finalWidth;
                    item.finalHeight = prepared.finalHeight;

                    await uploadPreparedItem(item);
                })
                .catch(() => {
                    // Item state is already updated.
                });
        };

        const addFiles = (incomingFiles) => {
            Array.from(incomingFiles).forEach((file) => {
                const normalizedFile = normalizeIncomingFile(file);

                if (!(normalizedFile instanceof File) || !normalizedFile.type.startsWith('image/')) {
                    return;
                }

                if (items.some((item) => item.key === fileKey(normalizedFile))) {
                    return;
                }

                if (items.length >= maxFiles) {
                    return;
                }

                const item = {
                    key: fileKey(normalizedFile),
                    file: normalizedFile,
                    status: 'queued',
                    progress: 0,
                    token: null,
                    note: null,
                    error: null,
                    finalWidth: null,
                    finalHeight: null,
                    removed: false,
                    xhr: null,
                };

                items.push(item);
                render();
                queueUpload(item);
            });
        };

        uploader.peoplecineAddStagedFiles = (incomingFiles) => {
            addFiles(incomingFiles);
        };

        const extractPastedImages = (event) => {
            const clipboardData = event.clipboardData;

            if (!clipboardData) {
                return [];
            }

            if (clipboardData.files && clipboardData.files.length > 0) {
                return Array.from(clipboardData.files).filter((file) => file.type.startsWith('image/'));
            }

            const itemsFromClipboard = clipboardData.items ? Array.from(clipboardData.items) : [];

            return itemsFromClipboard
                .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
                .map((item) => item.getAsFile())
                .filter((file) => file instanceof File);
        };

        form.addEventListener('submit', (event) => {
            if (busyItems().length > 0) {
                event.preventDefault();
                summary.textContent = text.pleaseWait;
            }
        });

        trigger.addEventListener('click', () => input.click());

        target.addEventListener('click', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('[data-staged-trigger], .legacy-uploader__remove')) {
                return;
            }

            input.click();
        });

        target.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            target.addEventListener(eventName, (event) => {
                event.preventDefault();
                target.classList.add('legacy-uploader__dropzone--dragging');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            target.addEventListener(eventName, (event) => {
                event.preventDefault();
                target.classList.remove('legacy-uploader__dropzone--dragging');
            });
        });

        target.addEventListener('drop', (event) => {
            if (event.dataTransfer?.files?.length) {
                addFiles(event.dataTransfer.files);
            }
        });

        input.addEventListener('change', () => {
            const selectedFiles = input.files ? Array.from(input.files) : [];
            input.value = '';

            if (selectedFiles.length > 0) {
                addFiles(selectedFiles);
            }
        });

        form.addEventListener('paste', (event) => {
            const pastedImages = extractPastedImages(event);

            if (pastedImages.length === 0) {
                return;
            }

            event.preventDefault();
            addFiles(pastedImages);
        });

        form.addEventListener('peoplecine:add-staged-files', (event) => {
            const pastedFiles = event?.detail?.files;

            if (!Array.isArray(pastedFiles) || pastedFiles.length === 0) {
                return;
            }

            addFiles(pastedFiles);
        });

        render();
    });
});
