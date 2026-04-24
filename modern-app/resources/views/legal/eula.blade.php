@php
    $isThai = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThai ? $thai : $english;
@endphp

@extends('layouts.app', ['title' => $title ?? ($isThai ? 'ข้อตกลงการใช้งานซอฟต์แวร์' : 'Software License Agreement')])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('เอกสารทางกฎหมาย', 'Legal Document') }}</p>
        <h1>{{ $t('ข้อตกลงการใช้งานซอฟต์แวร์และเว็บไซต์ (EULA)', 'Software and Website End User License Agreement (EULA)') }}</h1>
        <p class="lede">
            {{ $t('เอกสารฉบับนี้กำหนดเงื่อนไขการใช้เว็บไซต์ PeopleCine ซอฟต์แวร์ ส่วนติดต่อผู้ใช้ ฐานข้อมูล เนื้อหา และบริการที่เกี่ยวข้อง', 'This document sets out the conditions for using the PeopleCine website, software, interface, database, content, and related services.') }}
        </p>

    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('1. คู่สัญญาและการยอมรับข้อตกลง', '1. Parties and acceptance of this agreement') }}</h2>
        </div>
        <div class="stack-list">
            <div class="stack-card">
                <p>{{ $t('ข้อตกลงนี้ทำขึ้นระหว่างผู้ให้บริการเว็บไซต์ PeopleCine และผู้ใช้ทุกคนที่เข้าถึง สมัครสมาชิก หรือใช้งานเว็บไซต์ ไม่ว่าจะในฐานะแขก สมาชิก หรือผู้ดูแลระบบ', 'This agreement is between the PeopleCine site operator and every person who accesses, registers for, or uses the site, whether as a guest, member, or administrator.') }}</p>
            </div>
            <div class="stack-card">
                <p>{{ $t('เมื่อผู้ใช้เข้าใช้งานเว็บไซต์ สมัครสมาชิก ส่งข้อความ โพสต์เนื้อหา หรือกดใช้ฟังก์ชันที่เว็บไซต์จัดให้ ถือว่าผู้ใช้ได้อ่าน เข้าใจ และยอมรับข้อตกลงนี้ในรูปแบบธุรกรรมทางอิเล็กทรอนิกส์ตามกฎหมายไทย', 'By using the site, creating an account, posting content, sending messages, or using any site feature, the user acknowledges and accepts this agreement as an electronic transaction under Thai law.') }}</p>
            </div>
            <div class="stack-card">
                <p>{{ $t('หากผู้ใช้ไม่ยอมรับข้อตกลงนี้ ผู้ใช้ต้องหยุดใช้งานเว็บไซต์และบริการทั้งหมดทันที', 'If the user does not accept this agreement, the user must stop using the website and all related services immediately.') }}</p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('2. ขอบเขตสิทธิการใช้งาน', '2. Scope of license') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('ผู้ให้บริการให้สิทธิแบบจำกัด ไม่ผูกขาด เพิกถอนได้ และไม่อาจโอนต่อ แก่ผู้ใช้เพื่อเข้าถึงและใช้งานเว็บไซต์ตามวัตถุประสงค์ปกติของชุมชน PeopleCine เท่านั้น', 'The operator grants the user a limited, non-exclusive, revocable, and non-transferable license to access and use the site only for the ordinary purposes of the PeopleCine community.') }}</li>
            <li>{{ $t('สิทธินี้ไม่รวมถึงสิทธิในการคัดลอก ดัดแปลง ถอดรหัส ทำวิศวกรรมย้อนกลับ ขายต่อ เผยแพร่ต่อสาธารณะ หรือใช้ระบบเพื่อสร้างบริการที่แข่งขันกับเว็บไซต์ เว้นแต่กฎหมายไทยบังคับให้ทำได้', 'This license does not include the right to copy, modify, decompile, reverse engineer, resell, publicly redistribute, or use the system to create a competing service, unless Thai law mandatorily permits such conduct.') }}</li>
            <li>{{ $t('ผู้ให้บริการสงวนสิทธิในซอฟต์แวร์ ฐานข้อมูล โค้ดต้นฉบับ องค์ประกอบภาพ การออกแบบ และเครื่องหมายที่เกี่ยวข้องทั้งหมด เว้นแต่จะระบุเป็นอย่างอื่นอย่างชัดแจ้ง', 'The operator reserves all rights in the software, database, source code, visual elements, design, and related marks unless expressly stated otherwise.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('3. บัญชีผู้ใช้และความปลอดภัย', '3. User accounts and security') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('ผู้ใช้ต้องให้ข้อมูลที่ถูกต้องและไม่ทำให้ผู้อื่นเข้าใจผิดในการสมัครสมาชิกหรือแก้ไขโปรไฟล์', 'Users must provide accurate information and must not mislead others when registering or editing profile information.') }}</li>
            <li>{{ $t('ผู้ใช้ต้องรับผิดชอบต่อการเก็บรักษาชื่อผู้ใช้ รหัสผ่าน และข้อมูลยืนยันตัวตนของตนเอง หากพบการใช้งานผิดปกติ ผู้ใช้ควรเปลี่ยนรหัสผ่านหรือแจ้งผู้ดูแลระบบทันที', 'Users are responsible for safeguarding their username, password, and authentication details. If unusual activity is detected, the user should change the password or notify an administrator immediately.') }}</li>
            <li>{{ $t('ผู้ให้บริการมีสิทธิระงับ ปิดใช้งาน รีเซ็ตรหัสผ่าน หรือยุติบัญชี เมื่อมีเหตุอันควรสงสัยว่ามีการใช้บัญชีโดยมิชอบ ฝ่าฝืนกฎหมาย หรือฝ่าฝืนข้อตกลงนี้', 'The operator may suspend, disable, reset, or terminate an account when there is reasonable suspicion of misuse, unlawful conduct, or violation of this agreement.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('4. เนื้อหาที่ผู้ใช้สร้างขึ้น', '4. User-generated content') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('ผู้ใช้ยังคงเป็นเจ้าของสิทธิในเนื้อหาที่ตนโพสต์เท่าที่กฎหมายอนุญาต แต่ผู้ใช้ให้สิทธิแก่ผู้ให้บริการในการจัดเก็บ แสดงผล สำรองข้อมูล แปลงรูปแบบทางเทคนิค และเผยแพร่เนื้อหานั้นภายในระบบเพื่อให้บริการเว็บไซต์', 'Users retain ownership of their posted content to the extent permitted by law, but grant the operator a license to store, display, back up, technically transform, and publish that content within the system for operation of the site.') }}</li>
            <li>{{ $t('ผู้ใช้รับรองว่าเนื้อหาที่ส่งเข้าสู่ระบบไม่ละเมิดลิขสิทธิ์ สิทธิส่วนบุคคล ความลับทางการค้า เครื่องหมายการค้า หรือสิทธิอื่นของบุคคลภายนอก', 'Users represent that content submitted to the system does not infringe copyright, privacy rights, trade secrets, trademarks, or other third-party rights.') }}</li>
            <li>{{ $t('ผู้ให้บริการมีสิทธิลบ ซ่อน แก้ไขการแสดงผล ระงับการเข้าถึง หรือเก็บหลักฐานของเนื้อหาที่อาจฝ่าฝืนกฎหมาย กฎชุมชน หรือคำสั่งของหน่วยงานที่มีอำนาจ', 'The operator may remove, hide, alter the display of, restrict access to, or preserve evidence of content that may violate law, community rules, or orders from competent authorities.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('5. การใช้งานต้องห้าม', '5. Prohibited use') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('ห้ามใช้เว็บไซต์เพื่อกระทำการที่ขัดต่อกฎหมายไทย รวมถึงแต่ไม่จำกัดเพียงการนำเข้าข้อมูลคอมพิวเตอร์ที่ผิดกฎหมาย หลอกลวง รบกวนระบบ หรือเข้าถึงระบบโดยไม่ได้รับอนุญาต', 'The site must not be used for conduct that violates Thai law, including but not limited to unlawful computer data input, fraud, disruption of systems, or unauthorized access.') }}</li>
            <li>{{ $t('ห้ามส่งสแปม โฆษณาที่ไม่ได้รับอนุญาต มัลแวร์ โค้ดอันตราย ลิงก์หลอกลวง หรือเนื้อหาที่มีวัตถุประสงค์โจมตีระบบหรือผู้ใช้รายอื่น', 'Users must not send spam, unauthorized advertising, malware, harmful code, phishing links, or content intended to attack the system or other users.') }}</li>
            <li>{{ $t('ห้ามเก็บรวบรวมข้อมูลส่วนบุคคลของสมาชิกคนอื่นจากเว็บไซต์ไปใช้ภายนอกโดยไม่มีฐานกฎหมายหรือความยินยอมที่เพียงพอตามกฎหมายไทย', 'Users must not collect other members’ personal data from the site for external use without a proper legal basis or sufficient consent under Thai law.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('6. ข้อมูลส่วนบุคคลและความเป็นส่วนตัว', '6. Personal data and privacy') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('การเก็บ ใช้ เปิดเผย และจัดการข้อมูลส่วนบุคคลของผู้ใช้จะต้องเป็นไปตามกฎหมายไทยที่ใช้บังคับ รวมถึงกฎหมายคุ้มครองข้อมูลส่วนบุคคลที่เกี่ยวข้อง', 'The collection, use, disclosure, and management of personal data must comply with applicable Thai law, including relevant personal data protection law.') }}</li>
            <li>{{ $t('ผู้ใช้ยอมรับว่าระบบอาจเก็บข้อมูลที่จำเป็นต่อการให้บริการ เช่น ข้อมูลบัญชี ประวัติการใช้งาน คุกกี้ บันทึกการเข้าใช้ และข้อมูลที่ผู้ใช้โพสต์เข้าสู่ระบบ', 'Users acknowledge that the system may retain data necessary for service operation, such as account information, usage history, cookies, access logs, and submitted content.') }}</li>
            <li>{{ $t('ในกรณีที่กฎหมาย คำสั่งศาล หรือคำสั่งของหน่วยงานรัฐกำหนด ผู้ให้บริการอาจต้องเปิดเผยข้อมูลบางส่วนตามขอบเขตที่กฎหมายอนุญาตหรือบังคับ', 'Where required by law, court order, or a lawful government request, the operator may disclose information to the extent permitted or required by law.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('7. การบำรุงรักษา การเปลี่ยนแปลง และการยุติบริการ', '7. Maintenance, changes, and termination') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('ผู้ให้บริการอาจแก้ไข ปรับปรุง ระงับบางส่วน หรือยุติบริการบางรายการได้ตามสมควรเพื่อการบำรุงรักษา ความปลอดภัย การปฏิบัติตามกฎหมาย หรือการบริหารระบบ', 'The operator may modify, improve, partially suspend, or discontinue any part of the service as reasonably necessary for maintenance, security, legal compliance, or system administration.') }}</li>
            <li>{{ $t('ผู้ให้บริการอาจแก้ไขข้อตกลงนี้เป็นครั้งคราว โดยการประกาศฉบับปรับปรุงบนเว็บไซต์ และการใช้งานต่อภายหลังการประกาศถือเป็นการยอมรับฉบับปรับปรุงนั้น', 'The operator may revise this agreement from time to time by publishing an updated version on the site, and continued use after publication constitutes acceptance of the revised version.') }}</li>
            <li>{{ $t('เมื่อบัญชีถูกยุติ สิทธิการใช้งานตามข้อตกลงนี้ย่อมสิ้นสุดลงทันที แต่ข้อกำหนดที่โดยสภาพควรยังมีผลต่อไป เช่น การจำกัดความรับผิด สิทธิในเนื้อหา และกฎหมายที่ใช้บังคับ ยังคงมีผลต่อไป', 'When an account is terminated, the license granted under this agreement ends immediately, but provisions that should survive by their nature, such as liability limitations, content rights, and governing-law clauses, remain effective.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('8. การรับประกันและข้อจำกัดความรับผิด', '8. Warranties and limitation of liability') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('เว็บไซต์และซอฟต์แวร์ให้บริการตามสภาพที่เป็นอยู่และตามที่มีอยู่ในขณะนั้น ผู้ให้บริการไม่รับประกันว่าเว็บไซต์จะทำงานได้ต่อเนื่อง ปลอดข้อผิดพลาด หรือเหมาะสมกับวัตถุประสงค์เฉพาะของผู้ใช้ทุกกรณี', 'The website and software are provided on an “as is” and “as available” basis. The operator does not warrant uninterrupted operation, error-free performance, or fitness for every specific user purpose.') }}</li>
            <li>{{ $t('เท่าที่กฎหมายไทยอนุญาต ผู้ให้บริการไม่รับผิดสำหรับความเสียหายทางอ้อม ความเสียหายพิเศษ ผลกำไรที่สูญหาย การสูญเสียข้อมูล หรือความเสียหายต่อธุรกิจอันเกิดจากการใช้หรือไม่สามารถใช้เว็บไซต์ได้', 'To the extent permitted by Thai law, the operator is not liable for indirect, special, consequential, lost-profit, lost-data, or business-interruption damages arising from use of or inability to use the site.') }}</li>
            <li>{{ $t('ไม่มีข้อใดในข้อตกลงนี้มีผลตัดสิทธิที่กฎหมายไทยห้ามจำกัด หรือยกเว้นความรับผิดในกรณีที่กฎหมายกำหนดให้ต้องรับผิดโดยเด็ดขาด', 'Nothing in this agreement excludes rights that Thai law does not permit to be waived or limits liability where Thai law requires responsibility as a matter of mandatory law.') }}</li>
        </ul>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('9. กฎหมายที่ใช้บังคับและเขตอำนาจ', '9. Governing law and jurisdiction') }}</h2>
        </div>
        <div class="stack-list">
            <div class="stack-card">
                <p>{{ $t('ข้อตกลงนี้อยู่ภายใต้และให้ตีความตามกฎหมายแห่งราชอาณาจักรไทย', 'This agreement is governed by and shall be interpreted under the laws of the Kingdom of Thailand.') }}</p>
            </div>
            <div class="stack-card">
                <p>{{ $t('หากเกิดข้อพิพาท คู่สัญญาควรพยายามเจรจาแก้ไขโดยสุจริตก่อน และหากไม่สามารถตกลงกันได้ ให้ดำเนินการตามกระบวนการและศาลที่มีเขตอำนาจตามกฎหมายไทย เว้นแต่กฎหมายบังคับจะกำหนดเป็นอย่างอื่น', 'In the event of a dispute, the parties should first attempt good-faith resolution. If that is unsuccessful, the matter shall proceed under Thai legal process and in the courts having jurisdiction under Thai law, unless mandatory law requires otherwise.') }}</p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('10. เบ็ดเตล็ด', '10. Miscellaneous') }}</h2>
        </div>
        <ul class="manual-list">
            <li>{{ $t('หากข้อกำหนดใดของข้อตกลงนี้ตกเป็นโมฆะ ใช้บังคับไม่ได้ หรือขัดต่อกฎหมาย ข้อกำหนดส่วนที่เหลือยังคงมีผลบังคับต่อไปเท่าที่กฎหมายอนุญาต', 'If any part of this agreement is invalid, unenforceable, or contrary to law, the remaining provisions remain effective to the extent permitted by law.') }}</li>
            <li>{{ $t('การที่ผู้ให้บริการไม่ใช้สิทธิหรือไม่บังคับใช้ข้อกำหนดใดในคราวหนึ่ง ไม่ถือเป็นการสละสิทธินั้นในอนาคต', 'A failure by the operator to exercise or enforce any right once does not constitute a waiver of that right in the future.') }}</li>
            <li>{{ $t('ฉบับภาษาไทยควรใช้เป็นฉบับอ้างอิงหลักสำหรับการใช้งานในประเทศไทย หากมีความแตกต่างในการตีความ และกฎหมายไทยกำหนดไว้เป็นอย่างอื่น ให้ใช้ตามกฎหมายไทยนั้น', 'For use in Thailand, the Thai-language version should be treated as the primary reference if interpretation differs, subject always to mandatory Thai law.') }}</li>
        </ul>
    </section>
@endsection
