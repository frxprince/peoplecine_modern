@extends('layouts.app', ['title' => $title ?? 'Admin Manual'])

@section('content')
    @php
        $isThaiUi = app()->getLocale() === 'th';
        $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
    @endphp

    <section class="panel panel--hero manual-hero">
        <p class="eyebrow">{{ __('Admin') }}</p>
        <h1>{{ $manualLabel }}</h1>
        <p class="lede">
            {{ $t(
                'คู่มือนี้เขียนสำหรับผู้ดูแลระบบที่ไม่ได้มีพื้นฐานด้านคอมพิวเตอร์ เพื่อใช้ดูแลสมาชิก ห้องเว็บบอร์ด แบนเนอร์ และเครื่องมือสำคัญของเว็บไซต์ทีละขั้นตอน',
                'This guide is written for non-technical administrators and explains member management, room settings, banners, and key site tools step by step.'
            ) }}
        </p>
        <div class="manual-callout">
            <strong>{{ $t('วิธีเปิดคู่มือนี้', 'How to open this guide') }}</strong>
            <p>{{ $t('หลังเข้าสู่ระบบในฐานะผู้ดูแลระบบ ให้เปิดเมนู Admin แล้วเลือก “คู่มือผู้ดูแลระบบ” ได้ตลอดเวลา', 'After signing in as an administrator, open the Admin menu and select “Admin Manual” at any time.') }}</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('ภาพรวมหน้าที่ของผู้ดูแลระบบ', 'Administrator responsibilities at a glance') }}</h2>
            <p>{{ $t('งานดูแลประจำวันของระบบใหม่ถูกแบ่งเป็น 3 หน้าหลัก เพื่อให้ใช้งานง่ายและลดการแก้ไขไฟล์โดยตรง', 'The new site splits routine administration into three main screens so daily work is easier and does not require direct file editing.') }}</p>
        </div>

        <div class="manual-checklist">
            <div class="manual-checklist__item">
                <strong>{{ $t('1. User Admin', '1. User Admin') }}</strong>
                <p>{{ $t('จัดการสมาชิก ค้นหา ปรับระดับ ระงับบัญชี รีเซ็ตรหัสผ่าน และทดสอบอีเมล', 'Manage members, search, change levels, disable accounts, reset passwords, and test email delivery.') }}</p>
            </div>
            <div class="manual-checklist__item">
                <strong>{{ $t('2. Room Admin', '2. Room Admin') }}</strong>
                <p>{{ $t('สร้างห้องเว็บบอร์ดใหม่ กำหนดสิทธิ์ผู้เข้าถึง ลำดับห้อง และการเก็บถาวร', 'Create new forum rooms and control access level, room ordering, and archive state.') }}</p>
            </div>
            <div class="manual-checklist__item">
                <strong>{{ $t('3. Banner Admin', '3. Banner Admin') }}</strong>
                <p>{{ $t('อัปโหลด ลบ และจัดลำดับภาพแบนเนอร์ของหน้าแรกและแถบซ้าย', 'Upload, delete, and reorder banner images for the landing page and the left panel.') }}</p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('หน้าจอ User Admin', 'User Admin screen') }}</h2>
            <p>{{ $t('หน้าจอนี้ใช้ดูแลสมาชิกทั้งหมดในระบบ และเป็นหน้าที่จะถูกใช้บ่อยที่สุดในงานแอดมินประจำวัน', 'This is the main screen for member administration and will usually be the most frequently used admin page.') }}</p>
        </div>

        <figure class="manual-shot">
            @include('admin.partials.manual-screen-users-overview')
            <figcaption>{{ $t('ภาพรวมหน้า User Admin ใช้ดูรายชื่อสมาชิก ค้นหา เรียงลำดับ ปรับระดับสมาชิก เปลี่ยนสถานะ และเปิดการจัดการรายบุคคล', 'The User Admin overview page shows the member list, search, sorting, membership levels, account status, and per-user actions.') }}</figcaption>
        </figure>

        <div class="manual-section-grid">
            <article class="manual-card">
                <h3>{{ $t('ใช้ทำอะไรได้บ้าง', 'What you can do here') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('ค้นหาสมาชิกจากรหัสสมาชิก ชื่อผู้ใช้ ชื่อที่แสดง หรืออีเมล', 'Search members by ID, username, display name, or email address.') }}</li>
                    <li>{{ $t('จัดเรียงข้อมูลตามรหัสสมาชิก ชื่อ อีเมล ระดับสมาชิก สถานะ หรือจำนวนคลิก', 'Sort the list by ID, name, email, member level, status, or click count.') }}</li>
                    <li>{{ $t('เปลี่ยนระดับสมาชิก เช่น สมาชิกทั่วไป VIP หรือผู้ดูแลระบบ', 'Change the member level, including regular user, VIP, or administrator.') }}</li>
                    <li>{{ $t('เปลี่ยนสถานะบัญชีเป็นใช้งาน ปิดใช้งาน หรือแบน', 'Switch an account between active, disabled, or banned states.') }}</li>
                    <li>{{ $t('เปิดหน้าสมาชิกในแท็บใหม่เพื่อตรวจสอบข้อมูลสาธารณะ', 'Open the member profile in a new tab for public-profile review.') }}</li>
                </ul>
            </article>

            <article class="manual-card">
                <h3>{{ $t('ตัวอย่างการใช้งาน', 'Example use cases') }}</h3>
                <ol class="manual-steps">
                    <li>{{ $t('ต้องการยกระดับสมาชิกเป็น VIP: ค้นหาสมาชิก เปลี่ยน Level เป็น 4 แล้วกด Save', 'To upgrade a member to VIP: search for the member, change Level to 4, then press Save.') }}</li>
                    <li>{{ $t('ต้องการระงับผู้ใช้งานชั่วคราว: เปลี่ยน Status เป็น Disabled หรือ Banned แล้วกด Save', 'To temporarily restrict an account: change Status to Disabled or Banned, then press Save.') }}</li>
                    <li>{{ $t('ต้องการลบสมาชิกหลายคน: ติ๊กเครื่องหมายหน้าแต่ละชื่อ แล้วกด Delete / Disable Selected', 'To remove many members at once: tick the checkboxes and press Delete / Disable Selected.') }}</li>
                </ol>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('ค้นหาสมาชิกและทดสอบอีเมล', 'Live search and mail test') }}</h2>
            <p>{{ $t('ในระบบใหม่ กล่องค้นหาจะค้นหาแบบสดทันทีเมื่อพิมพ์ และ Mail Test ช่วยตรวจสอบว่าระบบส่งอีเมลออกได้จริงหรือไม่', 'In the new site, the member search updates live while you type, and Mail Test helps confirm that outgoing email works correctly.') }}</p>
        </div>

        <figure class="manual-shot">
            @include('admin.partials.manual-screen-users-tools')
            <figcaption>{{ $t('ตัวอย่างการค้นหาสมาชิกและการเปิดกล่อง Mail Test สำหรับทดสอบการส่งอีเมลจากระบบ', 'Example of live member search and the Mail Test box for checking outgoing email delivery.') }}</figcaption>
        </figure>

        <div class="manual-section-grid">
            <article class="manual-card">
                <h3>{{ $t('การค้นหาแบบสด', 'Live member search') }}</h3>
                <p>{{ $t('เมื่อพิมพ์ในช่องค้นหา รายชื่อจะรีเฟรชโดยอัตโนมัติในช่วงสั้น ๆ จึงไม่จำเป็นต้องกด Search ทุกครั้ง หากต้องการกลับมาดูรายชื่อทั้งหมด ให้กด Clear', 'When you type in the search box, the list refreshes automatically after a short delay, so you do not need to press Search every time. Use Clear to return to the full member list.') }}</p>
            </article>

            <article class="manual-card">
                <h3>{{ $t('ทดสอบการส่งอีเมล', 'Testing outgoing email') }}</h3>
                <ol class="manual-steps">
                    <li>{{ $t('เปิดกล่อง Mail Test', 'Open the Mail Test box.') }}</li>
                    <li>{{ $t('กรอกอีเมลปลายทาง หัวเรื่อง และข้อความสั้น ๆ', 'Enter the destination email, subject, and a short message.') }}</li>
                    <li>{{ $t('กด Send Test Email แล้วตรวจสอบกล่องจดหมายปลายทาง', 'Press Send Test Email and check the recipient inbox.') }}</li>
                </ol>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('หน้าจอ Room Admin', 'Room Admin screen') }}</h2>
            <p>{{ $t('หน้าจอนี้ใช้สร้างห้องใหม่และเปลี่ยนสิทธิ์การเข้าถึงของห้องเดิม โดยไม่ต้องแก้ฐานข้อมูลหรือไฟล์ระบบเอง', 'This page is used to create new forum rooms and update access permissions on existing rooms without editing the database by hand.') }}</p>
        </div>

        <figure class="manual-shot">
            @include('admin.partials.manual-screen-rooms')
            <figcaption>{{ $t('หน้า Room Admin ใช้สร้างห้องใหม่ ปรับสิทธิ์การเข้าใช้งาน ลำดับการแสดงผล และสถานะการเก็บถาวร', 'The Room Admin page is used to create rooms and adjust access level, display order, and archive state.') }}</figcaption>
        </figure>

        <div class="manual-section-grid">
            <article class="manual-card">
                <h3>{{ $t('งานที่ทำได้ในหน้านี้', 'Tasks on this page') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('สร้างห้องใหม่ พร้อมชื่อไทย ชื่ออังกฤษ สีชื่อ และคำอธิบาย', 'Create a new room with Thai name, English name, title color, and description.') }}</li>
                    <li>{{ $t('กำหนด Access Level ของห้อง เช่น อ่านได้ทุกคน, VIP, หรือเฉพาะแอดมิน', 'Set the room access level, such as public, VIP, or admin only.') }}</li>
                    <li>{{ $t('เปลี่ยนลำดับการแสดงผลของห้องในหน้าเว็บบอร์ด', 'Change the display order of rooms on the forum page.') }}</li>
                    <li>{{ $t('ตั้งห้องเป็นเก็บถาวรเพื่อหยุดการใช้งานปกติ', 'Mark a room as archived when it should no longer be part of normal activity.') }}</li>
                </ul>
            </article>

            <article class="manual-card">
                <h3>{{ $t('ตัวอย่างการใช้งาน', 'Example use cases') }}</h3>
                <ol class="manual-steps">
                    <li>{{ $t('สร้างห้อง VIP ใหม่: กรอกชื่อห้อง เลือก Access Level เป็น 4 แล้วกด Create Room', 'Create a new VIP room: enter the room details, choose Access Level 4, then press Create Room.') }}</li>
                    <li>{{ $t('ย้ายห้องขึ้นด้านบน: ปรับตัวเลข Sort Order ให้ต่ำลง แล้วกด Save', 'Move a room higher in the list: lower its Sort Order number, then press Save.') }}</li>
                    <li>{{ $t('ปิดห้องเก่าแต่ยังเก็บไว้: ติ๊ก Archive แล้วกด Save', 'Keep an old room but retire it from normal use: tick Archive and press Save.') }}</li>
                </ol>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('หน้าจอ Banner Admin', 'Banner Admin screen') }}</h2>
            <p>{{ $t('แบนเนอร์ของหน้าแรกและแถบซ้ายสามารถจัดการได้จากหน้าจอนี้ทั้งหมด ไม่ต้องอัปโหลดไฟล์ด้วย SFTP สำหรับงานประจำวันอีกต่อไป', 'Landing-page and left-panel banners can now be managed from one screen, so routine updates no longer require SFTP file work.') }}</p>
        </div>

        <figure class="manual-shot">
            @include('admin.partials.manual-screen-banners')
            <figcaption>{{ $t('หน้า Banner Admin ใช้อัปโหลด จัดลำดับ และลบแบนเนอร์ของหน้าแรกและแถบซ้าย', 'The Banner Admin page is used to upload, reorder, and remove landing-page and left-panel banners.') }}</figcaption>
        </figure>

        <div class="manual-section-grid">
            <article class="manual-card">
                <h3>{{ $t('งานที่ทำได้ในหน้านี้', 'Tasks on this page') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('เพิ่มแบนเนอร์ใหม่สำหรับหน้าแรกหรือแถบซ้าย', 'Add a new banner to the landing page or the left panel.') }}</li>
                    <li>{{ $t('กำหนดคำอธิบายรูป (Alt Text) เพื่อให้ดูแลไฟล์ได้ง่ายขึ้น', 'Set Alt Text so banner files are easier to identify and manage.') }}</li>
                    <li>{{ $t('ปรับลำดับการแสดงผลด้วยค่า Sort Order', 'Change the visual order using Sort Order.') }}</li>
                    <li>{{ $t('ลบแบนเนอร์ที่ไม่ใช้งานแล้ว', 'Delete banners that are no longer needed.') }}</li>
                </ul>
            </article>

            <article class="manual-card">
                <h3>{{ $t('ตัวอย่างการใช้งาน', 'Example use cases') }}</h3>
                <ol class="manual-steps">
                    <li>{{ $t('เพิ่มภาพประชาสัมพันธ์หน้าแรก: เลือก Banner Area เป็นหน้าแรก เลือกรูป แล้วกด Upload Banner', 'Add a new landing-page promotion: choose the landing area, select the image, then press Upload Banner.') }}</li>
                    <li>{{ $t('สลับลำดับรูป: แก้ค่า Sort Order ของรูปที่ต้องการ แล้วกด Save', 'Reorder banners: change the Sort Order on the relevant banner and press Save.') }}</li>
                    <li>{{ $t('ลบรูปเก่า: กด Delete ที่รูปนั้น แล้วยืนยันการลบ', 'Remove an old banner: press Delete on that banner and confirm the action.') }}</li>
                </ol>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('สิ่งที่เพิ่มหรือเปลี่ยนจากเว็บไซต์เดิม', 'What changed compared with the old website') }}</h2>
            <p>{{ $t('ส่วนนี้ช่วยให้ผู้ดูแลระบบเดิมเข้าใจว่าอะไรถูกย้ายมาไว้ในเมนูแอดมินของเว็บไซต์ใหม่ และอะไรที่ไม่ต้องทำแบบเดิมแล้ว', 'This section helps existing administrators see what has been centralized in the new admin menu and what no longer needs the older manual workflow.') }}</p>
        </div>

        <div class="manual-compare-grid">
            <article class="manual-card">
                <h3>{{ $t('ฟีเจอร์ที่เพิ่มเข้ามา', 'Added features') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('หน้า User Admin สำหรับค้นหา เรียงลำดับ รีเซ็ตรหัสผ่าน ลบ/ปิดใช้งานหลายบัญชี และทดสอบอีเมล', 'A dedicated User Admin page for searching, sorting, password reset, bulk delete/disable, and email testing.') }}</li>
                    <li>{{ $t('หน้า Room Admin สำหรับสร้างห้องใหม่และปรับสิทธิ์การเข้าถึงเอง', 'A dedicated Room Admin page for creating rooms and changing access levels directly.') }}</li>
                    <li>{{ $t('หน้า Banner Admin สำหรับจัดการแบนเนอร์จากหน้าเว็บ', 'A Banner Admin page for banner uploads and ordering directly from the site.') }}</li>
                    <li>{{ $t('คู่มือผู้ดูแลระบบในตัวเว็บไซต์ เปิดอ่านได้จากเมนู Admin', 'An in-site administrator manual available directly from the Admin menu.') }}</li>
                </ul>
            </article>

            <article class="manual-card">
                <h3>{{ $t('ฟีเจอร์ที่เปลี่ยนวิธีใช้งาน', 'Changed workflows') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('คู่มือ PDF ถูกแยกไปไว้ในเมนู Projector manual แทนเมนูบทความแบบเดิม', 'Projector manuals are published under the Projector manual menu instead of the old article menu flow.') }}</li>
                    <li>{{ $t('สิทธิ์ห้องเว็บบอร์ดแสดงเป็นป้าย LV บนชื่อห้อง เพื่อบอกระดับที่ต้องใช้เข้าห้อง', 'Restricted rooms now show an LV badge so the required access level is visible in the room list.') }}</li>
                    <li>{{ $t('ภาพแบนเนอร์และข้อมูลการตั้งค่าถูกเก็บในระบบใหม่ จึงไม่ควรแก้จากไฟล์โดยตรงสำหรับงานประจำวัน', 'Banner files and settings now live in the new management flow, so routine updates should not be done by direct file editing.') }}</li>
                </ul>
            </article>

            <article class="manual-card">
                <h3>{{ $t('สิ่งที่ไม่ต้องทำแบบเดิมแล้ว', 'What you no longer need to do the old way') }}</h3>
                <ul class="manual-list">
                    <li>{{ $t('ไม่จำเป็นต้องแก้สิทธิ์ห้องหรือเรียงห้องจากฐานข้อมูลโดยตรง', 'There is no need to edit room access or ordering directly in the database.') }}</li>
                    <li>{{ $t('ไม่จำเป็นต้องอัปโหลดแบนเนอร์ด้วย SFTP สำหรับการปรับเปลี่ยนตามปกติ', 'Routine banner changes no longer require SFTP uploads.') }}</li>
                    <li>{{ $t('ไม่จำเป็นต้องพึ่งการส่งอีเมลแบบเดิมโดยไม่มีหน้าทดสอบ เพราะตอนนี้มี Mail Test ในหน้าแอดมินแล้ว', 'You no longer have to guess whether email works because the admin area now includes a Mail Test tool.') }}</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('คำแนะนำก่อนใช้งานจริง', 'Practical admin tips') }}</h2>
        </div>

        <ul class="manual-list">
            <li>{{ $t('ทุกครั้งที่เปลี่ยนสิทธิ์สมาชิกหรือสิทธิ์ห้อง ควรรีเฟรชหน้าเว็บอีกครั้งเพื่อตรวจสอบผลลัพธ์', 'After changing member or room permissions, refresh the page once to confirm the result.') }}</li>
            <li>{{ $t('ก่อนลบแบนเนอร์ ควรตรวจสอบก่อนว่าภาพนั้นยังถูกใช้งานในหน้าแรกหรือแถบซ้ายหรือไม่', 'Before deleting a banner, confirm that it is no longer needed on the landing page or the left panel.') }}</li>
            <li>{{ $t('หากใช้ Mail Test แล้วไม่ได้รับอีเมล ให้ตรวจสอบกล่องสแปมหรือค่าระบบอีเมลของเซิร์ฟเวอร์', 'If Mail Test does not arrive, check the spam folder and the server mail configuration.') }}</li>
            <li>{{ $t('คู่มือนี้เป็นจุดเริ่มต้นสำหรับงานแอดมินประจำวัน แต่การแก้ปัญหาด้านระบบลึก ๆ ยังควรให้ผู้ดูแลเซิร์ฟเวอร์ช่วยตรวจสอบ', 'This guide covers routine admin work, while deeper server or deployment issues should still be handled with help from the system maintainer.') }}</li>
        </ul>
    </section>
@endsection
