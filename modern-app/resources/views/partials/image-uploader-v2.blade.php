@php
    $uploaderId = $inputId ?? 'attachments';
    $uploaderLabel = $label ?? __('Images');
    $uploaderAccept = $accept ?? '.jpg,.jpeg,.png,.gif,.bmp,.webp,image/*';
    $uploaderMaxFiles = max(1, (int) ($maxFiles ?? config('peoplecine.post_image_limit', 12)));
    $uploaderMaxWidth = max(1, (int) ($maxWidth ?? config('peoplecine.post_image_max_width', 1920)));
    $uploaderMaxHeight = max(1, (int) ($maxHeight ?? config('peoplecine.post_image_max_height', 1080)));
    $uploaderResizeQuality = max(0.1, min(1, (float) ($resizeQuality ?? config('peoplecine.post_image_resize_quality', 0.9))));
    $uploaderHiddenName = $hiddenName ?? 'staged_uploads[]';
    $uploaderHint = $hint ?? __('Drag and drop or select up to :count images.', ['count' => $uploaderMaxFiles]);
@endphp

<div
    class="legacy-uploader"
    data-staged-uploader
    data-staged-upload-url="{{ route('composer.uploads.store') }}"
    data-staged-remove-url-base="{{ url('/composer/uploads') }}"
    data-staged-hidden-input-name="{{ $uploaderHiddenName }}"
    data-staged-max-files="{{ $uploaderMaxFiles }}"
    data-staged-max-width="{{ $uploaderMaxWidth }}"
    data-staged-max-height="{{ $uploaderMaxHeight }}"
    data-staged-resize-quality="{{ $uploaderResizeQuality }}"
    data-text-no-images-uploaded="{{ __('No images uploaded yet.') }}"
    data-text-uploaded-summary="{{ __(':count uploaded', ['count' => '__COUNT__']) }}"
    data-text-in-progress-summary="{{ __(':count in progress', ['count' => '__COUNT__']) }}"
    data-text-failed-summary="{{ __(':count failed', ['count' => '__COUNT__']) }}"
    data-text-uploading="{{ __('Uploading :percent%', ['percent' => '__PERCENT__']) }}"
    data-text-uploaded-ready="{{ __('Uploaded and ready to post.') }}"
    data-text-preparing-image="{{ __('Preparing image...') }}"
    data-text-waiting-upload="{{ __('Waiting to upload...') }}"
    data-text-cancel="{{ __('Cancel') }}"
    data-text-remove="{{ __('Remove') }}"
    data-text-network-error="{{ __('Network error while uploading image.') }}"
    data-text-upload-failed="{{ __('Upload failed.') }}"
    data-text-please-wait="{{ __('Please wait for image uploads to finish before posting.') }}"
    data-text-gif-original="{{ __('GIF kept original to avoid breaking animation.') }}"
    data-text-browser-resize-unavailable="{{ __('Browser resize unavailable, original kept.') }}"
    data-text-resized="{{ __('Resized from :from to :to.', ['from' => '__FROM__', 'to' => '__TO__']) }}"
>
    <label class="legacy-uploader__label" for="{{ $uploaderId }}">{{ $uploaderLabel }}</label>

    <div
        class="legacy-uploader__dropzone"
        data-staged-dropzone-target
        tabindex="0"
        role="button"
        aria-controls="{{ $uploaderId }}"
    >
        <input
            id="{{ $uploaderId }}"
            type="file"
            accept="{{ $uploaderAccept }}"
            multiple
            data-staged-input
        >

        <p class="legacy-uploader__title">{{ __('Drop images here') }}</p>
        <p class="legacy-uploader__hint">{{ $uploaderHint }}</p>
        <p class="legacy-uploader__hint">{{ __('Large images will be resized to fit within :size before upload.', ['size' => $uploaderMaxWidth.'x'.$uploaderMaxHeight]) }}</p>
        <button class="button button--small button--ghost legacy-uploader__button" type="button" data-staged-trigger>
            {{ __('Choose Images') }}
        </button>
    </div>

    <div class="legacy-uploader__summary" data-staged-summary>{{ __('No images uploaded yet.') }}</div>
    <ul class="legacy-uploader__list" data-staged-list hidden></ul>
    <div data-staged-hidden-inputs></div>
</div>
