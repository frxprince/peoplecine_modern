@php
    $fieldId = $id ?? $name;
    $fieldName = $name;
    $fieldLabel = $label ?? 'Message';
    $fieldRows = $rows ?? 8;
    $fieldValue = old($fieldName, $value ?? '');
    $fieldPlaceholder = $placeholder ?? '';
    $fieldErrorKey = $errorKey ?? $fieldName;
    $fieldHeight = $height ?? max(280, $fieldRows * 34);
@endphp

<div class="tinymce-editor" data-tinymce-editor>
    <textarea
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        rows="{{ $fieldRows }}"
        class="tinymce-editor__textarea"
        data-tinymce-textarea
        data-editor-label="{{ $fieldLabel }}"
        data-editor-placeholder="{{ $fieldPlaceholder }}"
        data-editor-height="{{ $fieldHeight }}"
    >{{ $fieldValue }}</textarea>

    @if ($errors->has($fieldErrorKey))
        <p class="form-error">{{ $errors->first($fieldErrorKey) }}</p>
    @endif
</div>
