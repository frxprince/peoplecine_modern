@php
    $uploaderId = $inputId ?? 'attachments';
    $uploaderName = $inputName ?? 'attachments[]';
    $uploaderLabel = $label ?? __('Images');
    $uploaderAccept = $accept ?? '.jpg,.jpeg,.png,.gif,.bmp,.webp,image/*';
    $uploaderMaxFiles = max(1, (int) ($maxFiles ?? config('peoplecine.post_image_limit', 12)));
    $uploaderMaxWidth = max(1, (int) ($maxWidth ?? config('peoplecine.post_image_max_width', 1920)));
    $uploaderMaxHeight = max(1, (int) ($maxHeight ?? config('peoplecine.post_image_max_height', 1080)));
    $uploaderResizeQuality = max(0.1, min(1, (float) ($resizeQuality ?? config('peoplecine.post_image_resize_quality', 0.9))));
    $uploaderHint = $hint ?? __('Drag and drop or select up to :count images.', ['count' => $uploaderMaxFiles]);
@endphp

<div
    class="legacy-uploader"
    data-upload-dropzone
    data-max-files="{{ $uploaderMaxFiles }}"
    data-max-width="{{ $uploaderMaxWidth }}"
    data-max-height="{{ $uploaderMaxHeight }}"
    data-resize-quality="{{ $uploaderResizeQuality }}"
    data-text-no-images-selected="{{ __('No images selected.') }}"
    data-text-images-ready="{{ __(':count images ready to upload.', ['count' => '__COUNT__']) }}"
    data-text-remove="{{ __('Remove') }}"
    data-text-preparing="{{ __('Preparing images for upload...') }}"
    data-text-prepare-error="{{ __('Some images could not be prepared. Please try again.') }}"
    data-text-gif-original="{{ __('GIF kept original to avoid breaking animation.') }}"
    data-text-browser-resize-unavailable="{{ __('Browser resize unavailable, original kept.') }}"
    data-text-resized="{{ __('Resized from :from to :to.', ['from' => '__FROM__', 'to' => '__TO__']) }}"
>
    <label class="legacy-uploader__label" for="{{ $uploaderId }}">{{ $uploaderLabel }}</label>

    <div
        class="legacy-uploader__dropzone"
        data-upload-dropzone-target
        tabindex="0"
        role="button"
        aria-controls="{{ $uploaderId }}"
    >
        <input
            id="{{ $uploaderId }}"
            name="{{ $uploaderName }}"
            type="file"
            accept="{{ $uploaderAccept }}"
            multiple
            data-upload-input
        >

        <p class="legacy-uploader__title">{{ __('Drop images here') }}</p>
        <p class="legacy-uploader__hint">{{ $uploaderHint }}</p>
        <p class="legacy-uploader__hint">Large images will be resized to fit within {{ $uploaderMaxWidth }}×{{ $uploaderMaxHeight }} before upload.</p>
        <button class="button button--small button--ghost legacy-uploader__button" type="button" data-upload-trigger>
            {{ __('Choose Images') }}
        </button>
    </div>

    <div class="legacy-uploader__summary" data-upload-summary>{{ __('No images selected.') }}</div>
    <ul class="legacy-uploader__list" data-upload-list hidden></ul>
</div>
