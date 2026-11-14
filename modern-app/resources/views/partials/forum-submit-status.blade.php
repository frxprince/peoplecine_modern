@php($submissionText = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)
<dialog
    class="forum-submit-dialog"
    data-forum-submit-dialog
    data-topic-title="{{ $submissionText('กำลังโพสต์หัวข้อ', 'Posting your topic') }}"
    data-reply-title="{{ $submissionText('กำลังโพสต์คำตอบ', 'Posting your reply') }}"
    aria-labelledby="forum-submit-title"
    aria-describedby="forum-submit-description"
>
    <span class="forum-submit-dialog__spinner" aria-hidden="true"></span>
    <div role="status" aria-live="polite" aria-atomic="true">
        <h2 id="forum-submit-title" data-forum-submit-title tabindex="-1" autofocus>{{ $submissionText('กรุณารอสักครู่', 'Please wait') }}</h2>
        <p id="forum-submit-description">{{ $submissionText('กำลังบันทึกข้อความและรูปภาพของคุณ กรุณารอสักครู่ อย่าปิดหน้านี้หรือกดโพสต์ซ้ำ', 'Your post and images are being saved. Please keep this page open and do not post again.') }}</p>
    </div>
</dialog>
