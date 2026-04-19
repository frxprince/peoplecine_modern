@php
    $isEnglish = app()->getLocale() === 'en';
    $calculatorI18n = [
        'meterUnit' => $isEnglish ? 'm' : 'เมตร',
        'inchUnit' => $isEnglish ? 'in' : 'นิ้ว',
        'millimeterUnit' => $isEnglish ? 'mm' : 'มิลลิเมตร',
        'scope' => $isEnglish ? 'Scope' : 'สโคป',
        'flat' => $isEnglish ? 'Flat' : 'ตัดซีน',
        'scopeAndFlat' => $isEnglish ? 'Scope + Flat' : 'สโคป + ตัดซีน',
        'scopeScreen' => $isEnglish ? 'Scope screen' : 'จอสโคป',
        'flatScreen' => $isEnglish ? 'Flat screen' : 'จอตัดซีน',
        'recommendedFlatLens' => $isEnglish ? 'Recommended lens for flat projection:' : 'เลนส์ที่แนะนำสำหรับฉายตัดซีน:',
        'recommendedScopeLens' => $isEnglish ? 'Recommended lens for scope projection:' : 'เลนส์ที่แนะนำสำหรับฉายสโคป:',
    ];
@endphp
<script>
    window.peoplecineCalculatorI18n = @json($calculatorI18n);
</script>