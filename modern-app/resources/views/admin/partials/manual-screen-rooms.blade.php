@php
    $isThaiUi = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
@endphp

<div class="manual-screen">
    <div class="manual-screen__topbar">
        <div>
            <strong>PeopleCine Admin</strong>
            <span>{{ $t('ภาพรวมหน้าจอจัดการห้อง', 'Room Admin overview') }}</span>
        </div>
        <span class="manual-screen__badge">{{ $t('แอดมิน > จัดการห้อง', 'Admin > Room Admin') }}</span>
    </div>
    <div class="manual-screen__body">
        <aside class="manual-screen__sidebar">
            <span class="manual-screen__menu-item">{{ $t('จัดการผู้ใช้', 'User Admin') }}</span>
            <span class="manual-screen__menu-item manual-screen__menu-item--active">{{ $t('จัดการห้อง', 'Room Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('จัดการแบนเนอร์', 'Banner Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('คู่มือผู้ดูแลระบบ', 'Admin Manual') }}</span>
        </aside>
        <div class="manual-screen__content">
            <div class="manual-screen__hero">
                <div>
                    <h3>{{ $t('จัดการห้องเว็บบอร์ด', 'Room Management') }}</h3>
                    <p>{{ $t('สร้างห้องใหม่ กำหนดสิทธิ์การเข้าถึง ลำดับห้อง และสถานะเก็บถาวร', 'Create rooms, set access level, room order, and archive state.') }}</p>
                </div>
                <span class="manual-screen__badge">{{ $t('43 ห้อง', '43 imported rooms') }}</span>
            </div>
            <div class="manual-screen__columns">
                <div class="manual-screen__panel">
                    <strong>{{ $t('สร้างห้องใหม่', 'Create New Room') }}</strong>
                    <div class="manual-screen__form-grid">
                        <span class="manual-screen__field">{{ $t('ชื่อห้อง: VIP Projector Club', 'Room Name: VIP Projector Club') }}</span>
                        <span class="manual-screen__field">Slug: vip-projector-club</span>
                        <span class="manual-screen__field">{{ $t('ระดับสิทธิ์: 4 VIP', 'Access Level: 4 VIP') }}</span>
                        <span class="manual-screen__field">{{ $t('ลำดับ: 25', 'Sort Order: 25') }}</span>
                        <span class="manual-screen__field manual-screen__field--wide">{{ $t('คำอธิบาย: ห้องสนทนาสำหรับสมาชิก VIP เท่านั้น', 'Description: Private discussion room for VIP members only.') }}</span>
                    </div>
                </div>
                <div class="manual-screen__panel">
                    <strong>{{ $t('รายการห้องเว็บบอร์ด', 'Forum Rooms') }}</strong>
                    <table class="manual-screen__table">
                        <thead>
                            <tr>
                                <th>{{ $t('ห้อง', 'Room') }}</th>
                                <th>{{ $t('หัวข้อ', 'Topics') }}</th>
                                <th>{{ $t('สิทธิ์', 'Access') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $t('เว็บบอร์ดหลัก', 'Main Forum') }}</td>
                                <td>9,218</td>
                                <td>0 Read All</td>
                            </tr>
                            <tr>
                                <td>VIP Projector Club</td>
                                <td>201</td>
                                <td>4 VIP</td>
                            </tr>
                            <tr>
                                <td>{{ $t('คลังของทีมงาน', 'Staff Archive') }}</td>
                                <td>89</td>
                                <td>9 Admin Only</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
