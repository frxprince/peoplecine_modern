@php
    $isThaiUi = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
@endphp

<div class="manual-screen">
    <div class="manual-screen__topbar">
        <div>
            <strong>PeopleCine Admin</strong>
            <span>{{ $t('ตัวอย่างการค้นหาและทดสอบอีเมล', 'User Admin live search and mail test') }}</span>
        </div>
        <span class="manual-screen__badge">{{ $t('แอดมิน > จัดการผู้ใช้', 'Admin > User Admin') }}</span>
    </div>
    <div class="manual-screen__body">
        <aside class="manual-screen__sidebar">
            <span class="manual-screen__menu-item manual-screen__menu-item--active">{{ $t('จัดการผู้ใช้', 'User Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('จัดการห้อง', 'Room Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('จัดการแบนเนอร์', 'Banner Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('คู่มือผู้ดูแลระบบ', 'Admin Manual') }}</span>
        </aside>
        <div class="manual-screen__content">
            <div class="manual-screen__hero">
                <div>
                    <h3>{{ $t('ทดสอบอีเมล', 'Mail Test') }}</h3>
                    <p>{{ $t('ตรวจสอบว่าระบบ Laravel ส่งอีเมลออกได้จริงตามค่าตั้งค่าปัจจุบัน', 'Check whether Laravel can send email with the current server configuration.') }}</p>
                </div>
                <span class="manual-screen__badge">{{ $t('คำค้น: frog', 'Search: frog') }}</span>
            </div>
            <div class="manual-screen__panel">
                <div class="manual-screen__form-grid">
                    <span class="manual-screen__field">{{ $t('อีเมลปลายทาง: peoplecine@drpaween.com', 'Recipient email: peoplecine@drpaween.com') }}</span>
                    <span class="manual-screen__field">{{ $t('หัวเรื่อง: ทดสอบอีเมล PeopleCine', 'Subject: PeopleCine mail test') }}</span>
                    <span class="manual-screen__field manual-screen__field--wide">{{ $t('ข้อความ: นี่คืออีเมลทดสอบจากหน้าแอดมิน', 'Message: This is a test email from the admin panel.') }}</span>
                </div>
                <div class="manual-screen__button-row">
                    <span class="manual-screen__button">{{ $t('ส่งอีเมลทดสอบ', 'Send Test Email') }}</span>
                </div>
            </div>
            <div class="manual-screen__toolbar">
                <span class="manual-screen__search">frog</span>
                <span class="manual-screen__button">{{ $t('ค้นหา', 'Search') }}</span>
                <span class="manual-screen__button manual-screen__button--ghost">{{ $t('ล้างค่า', 'Clear') }}</span>
            </div>
            <table class="manual-screen__table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ $t('ผู้ใช้', 'User') }}</th>
                        <th>{{ $t('อีเมล', 'Email') }}</th>
                        <th>{{ $t('คลิก', 'Clicks') }}</th>
                        <th>{{ $t('สิทธิ์', 'Role') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1599</td>
                        <td>frogprince</td>
                        <td>peoplecine@drpaween.com</td>
                        <td>2,183</td>
                        <td>{{ $t('ผู้ดูแลระบบ', 'Admin') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
