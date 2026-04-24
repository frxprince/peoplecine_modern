@php
    $isThaiUi = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
@endphp

<div class="manual-screen">
    <div class="manual-screen__topbar">
        <div>
            <strong>PeopleCine Admin</strong>
            <span>{{ $t('ภาพรวมหน้าจอจัดการแบนเนอร์', 'Banner Admin overview') }}</span>
        </div>
        <span class="manual-screen__badge">{{ $t('แอดมิน > จัดการแบนเนอร์', 'Admin > Banner Admin') }}</span>
    </div>
    <div class="manual-screen__body">
        <aside class="manual-screen__sidebar">
            <span class="manual-screen__menu-item">{{ $t('จัดการผู้ใช้', 'User Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('จัดการห้อง', 'Room Admin') }}</span>
            <span class="manual-screen__menu-item manual-screen__menu-item--active">{{ $t('จัดการแบนเนอร์', 'Banner Admin') }}</span>
            <span class="manual-screen__menu-item">{{ $t('คู่มือผู้ดูแลระบบ', 'Admin Manual') }}</span>
        </aside>
        <div class="manual-screen__content">
            <div class="manual-screen__hero">
                <div>
                    <h3>{{ $t('จัดการแบนเนอร์', 'Banner Management') }}</h3>
                    <p>{{ $t('อัปโหลดแบนเนอร์สำหรับหน้าแรกและแถบซ้าย แล้วจัดลำดับหรือลบรูปได้ในหน้าเดียว', 'Upload banners for the landing page and left panel, then reorder or delete them.') }}</p>
                </div>
                <span class="manual-screen__badge">{{ $t('2 พื้นที่แบนเนอร์', '2 banner areas') }}</span>
            </div>
            <div class="manual-screen__panel">
                <strong>{{ $t('เพิ่มแบนเนอร์ใหม่', 'Add New Banner') }}</strong>
                <div class="manual-screen__form-grid">
                    <span class="manual-screen__field">{{ $t('พื้นที่แบนเนอร์: แบนเนอร์หน้าแรก', 'Banner Area: Landing Page Banners') }}</span>
                    <span class="manual-screen__field">{{ $t('ข้อความกำกับรูป: แบนเนอร์โปรโมชันหน้าร้อน', 'Alt Text: Summer promotion banner') }}</span>
                    <span class="manual-screen__field manual-screen__field--wide">{{ $t('ไฟล์ที่เลือก: summer-promo.jpg', 'Selected image: summer-promo.jpg') }}</span>
                </div>
                <div class="manual-screen__button-row">
                    <span class="manual-screen__button">{{ $t('อัปโหลดแบนเนอร์', 'Upload Banner') }}</span>
                </div>
            </div>
            <div class="manual-screen__columns">
                <div class="manual-screen__panel">
                    <div class="manual-screen__banner-thumb"></div>
                    <strong>{{ $t('แบนเนอร์หน้าแรก #1', 'Landing banner #1') }}</strong>
                    <div class="manual-screen__button-row">
                        <span class="manual-screen__button">{{ $t('บันทึก', 'Save') }}</span>
                        <span class="manual-screen__button manual-screen__button--danger">{{ $t('ลบ', 'Delete') }}</span>
                    </div>
                </div>
                <div class="manual-screen__panel">
                    <div class="manual-screen__banner-thumb"></div>
                    <strong>{{ $t('แบนเนอร์แถบซ้าย #2', 'Sidebar banner #2') }}</strong>
                    <div class="manual-screen__button-row">
                        <span class="manual-screen__button">{{ $t('บันทึก', 'Save') }}</span>
                        <span class="manual-screen__button manual-screen__button--danger">{{ $t('ลบ', 'Delete') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
