@php($submissionText = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)
<dialog
    class="forum-submit-dialog"
    data-forum-submit-dialog
    data-topic-title="{{ $submissionText('กำลังโพสต์หัวข้อ', 'Posting your topic') }}"
    data-reply-title="{{ $submissionText('กำลังโพสต์คำตอบ', 'Posting your reply') }}"
    data-delete-topic-title="{{ $submissionText('กำลังลบหัวข้อ', 'Deleting the topic') }}"
    data-delete-description="{{ $submissionText('กำลังลบหัวข้อและข้อความตอบ กรุณารอสักครู่ ระบบจะพากลับไปยังห้องเว็บบอร์ดโดยอัตโนมัติ', 'Please wait while the topic and its replies are deleted. You will return to the forum room automatically.') }}"
    data-error-title="{{ $submissionText('ยังยืนยันผลไม่ได้', 'Unable to confirm completion') }}"
    data-uncertain-message="{{ $submissionText('รายการนี้อาจบันทึกหรือลบเรียบร้อยแล้ว แต่ยังยืนยันผลไม่ได้ กรุณาตรวจสอบรายการล่าสุดก่อนลองอีกครั้ง เพื่อป้องกันการโพสต์ซ้ำ', 'The action may already be complete, but we could not confirm it. Check the latest posts before trying again to avoid duplicates.') }}"
    data-session-message="{{ $submissionText('เซสชันหมดอายุ กรุณาเก็บข้อความของคุณไว้ แล้วโหลดหน้าใหม่หรือเข้าสู่ระบบอีกครั้ง', 'Your session has expired. Keep a copy of your message, then reload the page or sign in again.') }}"
    data-permission-message="{{ $submissionText('คุณไม่มีสิทธิ์ทำรายการนี้ กรุณาตรวจสอบสถานะการเข้าสู่ระบบและสิทธิ์สมาชิก', 'You do not have permission for this action. Please check your sign-in status and member permissions.') }}"
    data-validation-message="{{ $submissionText('กรุณาตรวจสอบข้อมูลที่กรอกก่อนลองอีกครั้ง', 'Please check the form details before trying again.') }}"
    aria-labelledby="forum-submit-title"
    aria-describedby="forum-submit-description"
>
    <span class="forum-submit-dialog__spinner" data-forum-submit-spinner aria-hidden="true"></span>
    <div role="status" aria-live="polite" aria-atomic="true">
        <h2 id="forum-submit-title" data-forum-submit-title tabindex="-1" autofocus>{{ $submissionText('กรุณารอสักครู่', 'Please wait') }}</h2>
        <p id="forum-submit-description" data-forum-submit-description>{{ $submissionText('กำลังบันทึกข้อความและรูปภาพของคุณ กรุณารอสักครู่ อย่าปิดหน้านี้หรือกดโพสต์ซ้ำ', 'Your post and images are being saved. Please keep this page open and do not post again.') }}</p>
    </div>
    <div class="forum-submit-dialog__actions" data-forum-submit-actions hidden>
        <button class="button button--ghost button--small" type="button" data-forum-submit-close>{{ $submissionText('กลับไปดูข้อความ', 'Return to the page') }}</button>
        <button class="button button--small" type="button" data-forum-submit-recover>{{ $submissionText('ตรวจสอบรายการล่าสุด', 'Check latest posts') }}</button>
    </div>
</dialog>
