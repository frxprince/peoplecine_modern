@php
    $isThaiUi = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
@endphp

<div class="manual-screen">
    <div class="manual-screen__topbar">
        <div>
            <strong>PeopleCine Admin</strong>
            <span>{{ $t('ภาพรวมหน้าจอจัดการสมาชิก', 'User Admin overview') }}</span>
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
                    <h3>{{ $t('จัดการสมาชิก', 'User Management') }}</h3>
                    <p>{{ $t('รายชื่อสมาชิก การค้นหา การเรียงลำดับ ระดับสมาชิก สถานะ และปุ่มจัดการรายคน', 'Member list, search, sorting, level, status, and quick actions.') }}</p>
                </div>
                <span class="manual-screen__badge">{{ $t('1,599 บัญชี', '1,599 accounts') }}</span>
            </div>
            <div class="manual-screen__toolbar">
                <span class="manual-screen__search">{{ $t('ค้นหาจากรหัสสมาชิก ชื่อผู้ใช้ ชื่อที่แสดง หรืออีเมล', 'Search by ID, username, display name, or email') }}</span>
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
                        <th>{{ $t('ระดับ', 'Level') }}</th>
                        <th>{{ $t('สถานะ', 'Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1599</td>
                        <td>frogprince</td>
                        <td>peoplecine@drpaween.com</td>
                        <td>2,183</td>
                        <td>9</td>
                        <td>{{ $t('ใช้งาน', 'Active') }}</td>
                    </tr>
                    <tr>
                        <td>1598</td>
                        <td>retrocinema</td>
                        <td>retro@example.com</td>
                        <td>640</td>
                        <td>4</td>
                        <td>{{ $t('ใช้งาน', 'Active') }}</td>
                    </tr>
                    <tr>
                        <td>1597</td>
                        <td>oldmoviefan</td>
                        <td>oldmovie@example.com</td>
                        <td>114</td>
                        <td>3</td>
                        <td>{{ $t('ปิดใช้งาน', 'Disabled') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
