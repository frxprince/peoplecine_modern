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

    const loadImage = (file) => new Promise((resolve, reject) => {
        const imageUrl = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            resolve({
                image,
                width: image.naturalWidth || image.width,
                height: image.naturalHeight || image.height,
                imageUrl,
            });
        };

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
                resized: false,
                note: file.type === 'image/gif'
                    ? (text.gifOriginal ?? 'GIF kept original to avoid breaking animation.')
                    : null,
            };
        }

        const { image, width, height, imageUrl } = await loadImage(file);

        try {
            if (width <= maxWidth && height <= maxHeight) {
                return {
                    file,
                    key: fileKey(file),
                    resized: false,
                    originalWidth: width,
                    originalHeight: height,
                    finalWidth: width,
                    finalHeight: height,
                    note: null,
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
                    resized: false,
                    originalWidth: width,
                    originalHeight: height,
                    finalWidth: width,
                    finalHeight: height,
                    note: text.browserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
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
                    resized: false,
                    originalWidth: width,
                    originalHeight: height,
                    finalWidth: width,
                    finalHeight: height,
                    note: text.browserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
                };
            }

            const resizedFile = blobToFile(blob, file.name, outputType);

            return {
                file: resizedFile,
                key: fileKey(resizedFile),
                resized: true,
                originalWidth: width,
                originalHeight: height,
                finalWidth: targetWidth,
                finalHeight: targetHeight,
                note: translate(
                    text.resized ?? 'Resized from __FROM__ to __TO__.',
                    {
                        '__FROM__': formatDimensions(width, height),
                        '__TO__': formatDimensions(targetWidth, targetHeight),
                    },
                ),
            };
        } finally {
            URL.revokeObjectURL(imageUrl);
        }
    };

    document.querySelectorAll('[data-upload-dropzone]').forEach((uploader) => {
        const input = uploader.querySelector('[data-upload-input]');
        const target = uploader.querySelector('[data-upload-dropzone-target]');
        const trigger = uploader.querySelector('[data-upload-trigger]');
        const list = uploader.querySelector('[data-upload-list]');
        const summary = uploader.querySelector('[data-upload-summary]');
        const maxFiles = Number.parseInt(uploader.dataset.maxFiles ?? '12', 10) || 12;
        const maxWidth = Number.parseInt(uploader.dataset.maxWidth ?? '1920', 10) || 1920;
        const maxHeight = Number.parseInt(uploader.dataset.maxHeight ?? '1080', 10) || 1080;
        const resizeQuality = Number.parseFloat(uploader.dataset.resizeQuality ?? '0.9') || 0.9;
        const text = {
            noImagesSelected: uploader.dataset.textNoImagesSelected ?? 'No images selected.',
            imagesReady: uploader.dataset.textImagesReady ?? '__COUNT__ images ready to upload.',
            remove: uploader.dataset.textRemove ?? 'Remove',
            preparing: uploader.dataset.textPreparing ?? 'Preparing images for upload...',
            prepareError: uploader.dataset.textPrepareError ?? 'Some images could not be prepared. Please try again.',
            gifOriginal: uploader.dataset.textGifOriginal ?? 'GIF kept original to avoid breaking animation.',
            browserResizeUnavailable: uploader.dataset.textBrowserResizeUnavailable ?? 'Browser resize unavailable, original kept.',
            resized: uploader.dataset.textResized ?? 'Resized from __FROM__ to __TO__.',
        };

        if (!input || !target || !trigger || !list || !summary) {
            return;
        }

        let files = [];
        let pendingMerge = Promise.resolve();

        const syncInput = () => {
            if (typeof DataTransfer === 'undefined') {
                return;
            }

            const transfer = new DataTransfer();

            files.forEach((item) => transfer.items.add(item.file));

            input.files = transfer.files;
        };

        const render = () => {
            syncInput();

            if (files.length === 0) {
                summary.textContent = text.noImagesSelected;
                list.hidden = true;
                list.innerHTML = '';
                target.classList.remove('legacy-uploader__dropzone--has-files');

                return;
            }

            summary.textContent = translate(text.imagesReady, { '__COUNT__': files.length });
            list.hidden = false;
            target.classList.add('legacy-uploader__dropzone--has-files');
            list.innerHTML = '';

            files.forEach((fileItem, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'legacy-uploader__item';

                const meta = document.createElement('div');
                meta.className = 'legacy-uploader__item-meta';

                const nameLine = document.createElement('strong');
                nameLine.textContent = `${index + 1}. ${fileItem.file.name}`;

                const detailLine = document.createElement('span');
                const dimensionText = formatDimensions(fileItem.finalWidth, fileItem.finalHeight);
                detailLine.textContent = [
                    formatSize(fileItem.file.size),
                    dimensionText ? `${dimensionText}px` : '',
                ].filter(Boolean).join(' | ');

                meta.append(nameLine);

                if (detailLine.textContent !== '') {
                    meta.append(detailLine);
                }

                if (fileItem.note) {
                    const noteLine = document.createElement('small');
                    noteLine.textContent = fileItem.note;
                    meta.append(noteLine);
                }

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'legacy-uploader__remove';
                remove.textContent = text.remove;
                remove.addEventListener('click', () => {
                    files = files.filter((candidate) => candidate.key !== fileItem.key);
                    render();
                });

                listItem.append(meta, remove);
                list.append(listItem);
            });
        };

        const setBusy = (busy) => {
            uploader.classList.toggle('legacy-uploader--busy', busy);
            target.classList.toggle('legacy-uploader__dropzone--busy', busy);
            trigger.disabled = busy;
        };

        const mergeFiles = async (incoming) => {
            setBusy(true);
            summary.textContent = text.preparing;

            const merged = [...files];

            for (const file of Array.from(incoming)) {
                if (!(file instanceof File)) {
                    continue;
                }

                if (!file.type.startsWith('image/')) {
                    continue;
                }

                const prepared = await prepareImageFile(file, maxWidth, maxHeight, resizeQuality, text);

                if (merged.some((candidate) => candidate.key === prepared.key)) {
                    continue;
                }

                if (merged.length < maxFiles) {
                    merged.push(prepared);
                }
            }

            files = merged.slice(0, maxFiles);
            setBusy(false);
            render();
        };

        const queueMerge = (incoming) => {
            pendingMerge = pendingMerge
                .then(() => mergeFiles(incoming))
                .catch(() => {
                    setBusy(false);
                    summary.textContent = text.prepareError;
                });
        };

        trigger.addEventListener('click', () => input.click());

        target.addEventListener('click', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('[data-upload-trigger]')) {
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
                queueMerge(event.dataTransfer.files);
            }
        });

        input.addEventListener('change', () => {
            const selectedFiles = input.files ? Array.from(input.files) : [];

            input.value = '';

            if (selectedFiles.length) {
                queueMerge(selectedFiles);
            }
        });

        render();
    });
});
