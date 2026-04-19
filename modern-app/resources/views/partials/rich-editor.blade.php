@php
    $fieldId = $id ?? $name;
    $fieldName = $name;
    $fieldLabel = $label ?? 'Message';
    $fieldRows = $rows ?? 8;
    $fieldValue = old($fieldName, $value ?? '');
    $fieldPlaceholder = $placeholder ?? '';
    $fieldErrorKey = $errorKey ?? $fieldName;
@endphp

<div class="rich-editor" data-rich-editor>
    <div class="rich-editor__toolbar" data-rich-editor-toolbar hidden aria-label="{{ $fieldLabel }} formatting tools">
            <button class="rich-editor__button" type="button" data-editor-command="bold" title="{{ __('Bold') }}"><strong>B</strong></button>
            <button class="rich-editor__button" type="button" data-editor-command="italic" title="{{ __('Italic') }}"><em>I</em></button>
            <button class="rich-editor__button" type="button" data-editor-command="underline" title="{{ __('Underline') }}"><u>U</u></button>
            <button class="rich-editor__button" type="button" data-editor-command="insertUnorderedList" title="{{ __('Bullet list') }}">• {{ __('List') }}</button>
            <button class="rich-editor__button" type="button" data-editor-command="insertOrderedList" title="{{ __('Numbered list') }}">1. {{ __('List') }}</button>
            <button class="rich-editor__button" type="button" data-editor-action="link" title="{{ __('Insert link') }}">{{ __('Link') }}</button>
            <button class="rich-editor__button" type="button" data-editor-command="unlink" title="{{ __('Remove link') }}">{{ __('Unlink') }}</button>
            <button class="rich-editor__button" type="button" data-editor-command="removeFormat" title="{{ __('Clear formatting') }}">{{ __('Clear') }}</button>
    </div>

    <div
        class="rich-editor__surface"
        data-rich-editor-surface
        data-placeholder="{{ $fieldPlaceholder }}"
        aria-label="{{ $fieldLabel }}"
        contenteditable="true"
        hidden
    ></div>

    <textarea
        id="{{ $fieldId }}"
        name="{{ $fieldName }}"
        rows="{{ $fieldRows }}"
        data-rich-editor-textarea
        data-editor-label="{{ $fieldLabel }}"
        data-editor-placeholder="{{ $fieldPlaceholder }}"
    >{{ $fieldValue }}</textarea>

    @if ($errors->has($fieldErrorKey))
        <p class="form-error">{{ $errors->first($fieldErrorKey) }}</p>
    @endif
</div>
