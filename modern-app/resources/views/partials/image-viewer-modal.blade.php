<div class="image-viewer" data-image-viewer hidden>
    <div class="image-viewer__backdrop" data-image-viewer-close></div>
    <div class="image-viewer__dialog" role="dialog" aria-modal="true" aria-label="{{ __('Image viewer') }}">
        <button class="image-viewer__close" type="button" data-image-viewer-close aria-label="{{ __('Close image viewer') }}">
            <span aria-hidden="true">&times;</span>
        </button>
        <button class="image-viewer__nav image-viewer__nav--prev" type="button" data-image-viewer-prev aria-label="{{ __('Previous image') }}">
            <span aria-hidden="true">&#8249;</span>
        </button>
        <figure class="image-viewer__figure">
            <img class="image-viewer__image" data-image-viewer-image src="" alt="">
            <figcaption class="image-viewer__caption" data-image-viewer-caption></figcaption>
        </figure>
        <button class="image-viewer__nav image-viewer__nav--next" type="button" data-image-viewer-next aria-label="{{ __('Next image') }}">
            <span aria-hidden="true">&#8250;</span>
        </button>
    </div>
</div>
