(function () {
    function one(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function many(selector, scope) {
        return Array.from((scope || document).querySelectorAll(selector));
    }

    function checkedValue(name, fallback) {
        var selected = one('input[name="' + name + '"]:checked');

        return selected ? selected.value : fallback;
    }

    function numberValue(selector, fallback) {
        var field = one(selector);
        var raw = field ? parseFloat(field.value) : NaN;

        return Number.isFinite(raw) ? raw : fallback;
    }

    function setText(id, value) {
        var node = one('#' + id);

        if (node) {
            node.textContent = value;
        }
    }

    function setDisplay(id, visible) {
        var node = one('#' + id);

        if (node) {
            node.style.display = visible ? '' : 'none';
        }
    }

    function meters(value) {
        return value.toFixed(2) + ' เมตร';
    }

    function initThrowSelector() {
        var root = one('[data-calculator-page="throw-selector"]');
        var button = one('#btn1');

        if (! root || ! button) {
            return;
        }

        button.addEventListener('click', function () {
            var screen = checkedValue('screen', 'scope');
            var printFormat = checkedValue('print_format', 'scope');

            window.location.href = '/calculator/throw/' + screen + '?print_format=' + encodeURIComponent(printFormat);
        });
    }

    function initThrowCalculator() {
        var root = one('[data-calculator-page="throw"]');
        var button = one('#cal');

        if (! root || ! button) {
            return;
        }

        setDisplay('layer1', false);

        button.addEventListener('click', function (event) {
            event.preventDefault();

            var gateSize = 0.838;
            var screenType = root.dataset.throwScreen || 'scope';
            var printFormat = root.dataset.throwPrintFormat || 'scope';
            var focal = numberValue('#focal', 2.4) * parseFloat(checkedValue('focal_unit', '1'));
            var screenSize = numberValue('#width', 7) / parseFloat(checkedValue('width_unit', '1'));
            var throwDistance = 0;

            setDisplay('layer1', true);
            setDisplay('image_area_flat', false);
            setDisplay('image_scope', false);
            setText('Lmatch', '');

            if (screenType === 'scope') {
                if (printFormat === 'scope' || printFormat === 'both') {
                    throwDistance = (((((screenSize * 12 * 3.2808399) / gateSize) + 1) * focal) / 12) / 2;
                    setText('throw', meters(throwDistance * 0.3048));
                    setText('screen_width', meters(screenSize));
                    setText('screen_height', meters(screenSize / 2.35));
                    setText('Lscreen_type', 'สโคป');
                    setText('Lprint_type', 'สโคป');
                    setText('Lscreen_width', meters(screenSize));
                    setText('Lscreen_height', meters(screenSize / 2.35));
                    setText('Lthrow_distance', meters(throwDistance * 0.3048));

                    if (printFormat === 'both') {
                        var recommendFlatLens = focal * 0.6371428;

                        setDisplay('image_area_flat', true);
                        setText('screen_flat_width', meters((screenSize / 2.35) * 1.85));
                        setText('Lmatch', 'เลนส์ในที่แนะนำสำหรับฉายตัดซีน: ' + recommendFlatLens.toFixed(2) + ' นิ้ว (' + (recommendFlatLens / 0.03937008).toFixed(2) + ' มิลลิเมตร)');
                    }
                }

                if (printFormat === 'flat') {
                    var scopeScreenHeight = screenSize / 2.35;
                    var flatWidth = scopeScreenHeight * 1.85;

                    throwDistance = (((((flatWidth * 12 * 3.2808399) / gateSize) + 1) * focal) / 12);
                    setDisplay('image_area_flat', true);
                    setText('throw', meters(throwDistance * 0.3048));
                    setText('screen_width', meters(screenSize));
                    setText('screen_height', meters(scopeScreenHeight));
                    setText('screen_flat_width', meters(flatWidth));
                    setText('Lscreen_type', 'สโคป');
                    setText('Lprint_type', 'ตัดซีน');
                    setText('Lscreen_width', meters(screenSize));
                    setText('Lscreen_height', meters(scopeScreenHeight));
                    setText('Lthrow_distance', meters(throwDistance * 0.3048));
                }
            }

            if (screenType === 'flat') {
                setText('scope_label', 'สโคป');

                if (printFormat === 'scope') {
                    throwDistance = (((((screenSize * 12 * 3.2808399) / gateSize) + 1) * focal) / 12) / 2;
                    setDisplay('image_scope', true);
                    setText('throw', meters(throwDistance * 0.3048));
                    setText('screen_width', meters(screenSize));
                    setText('screen_height', meters(screenSize / 1.85));
                    setText('screen_scope_height', meters(screenSize / 2.35));
                    setText('Lscreen_type', 'จอตัดซีน');
                    setText('Lprint_type', 'สโคป');
                    setText('Lscreen_width', meters(screenSize));
                    setText('Lscreen_height', meters(screenSize / 2.35));
                    setText('Lthrow_distance', meters(throwDistance * 0.3048));
                }

                if (printFormat === 'flat' || printFormat === 'both') {
                    var screenHeight = screenSize / 1.85;

                    throwDistance = (((((screenSize * 12 * 3.2808399) / gateSize) + 1) * focal) / 12);
                    setText('throw', meters(throwDistance * 0.3048));
                    setText('screen_width', meters(screenSize));
                    setText('screen_height', meters(screenHeight));
                    setText('Lscreen_type', 'จอตัดซีน');
                    setText('Lprint_type', 'ตัดซีน');
                    setText('Lscreen_width', meters(screenSize));
                    setText('Lscreen_height', meters(screenHeight));
                    setText('Lthrow_distance', meters(throwDistance * 0.3048));

                    if (printFormat === 'both') {
                        var recommendScopeLens = focal * 2;

                        setDisplay('image_scope', true);
                        setText('screen_scope_height', meters(screenSize / 2.35));
                        setText('Lmatch', 'เลนส์ในที่แนะนำสำหรับฉายสโคป: ' + recommendScopeLens.toFixed(2) + ' นิ้ว (' + (recommendScopeLens / 0.03937008).toFixed(2) + ' มิลลิเมตร)');
                    }
                }
            }
        });
    }

    function initLensSimulation() {
        var root = one('[data-calculator-page="lenssim"]');
        var button = one('#cal');

        if (! root || ! button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();

            var focalScope = numberValue('#focal_scope', 2.4) * parseFloat(checkedValue('focal_scope_unit', '1'));
            var focalFlat = numberValue('#focal_flat', 2.4) * parseFloat(checkedValue('focal_flat_unit', '1'));
            var scopeWidth = 100;
            var flatWidth = ((focalScope / 2) / focalFlat) * scopeWidth;
            var maxHeight = Math.max(scopeWidth / 2.35, flatWidth / 1.85);
            var maxWidth = Math.max(scopeWidth, flatWidth);
            var scale = 350 / Math.max(maxHeight, maxWidth);
            var academyWidth = scopeWidth / 2;
            var scopeNode = one('#scope');
            var academyNode = one('#academy');
            var flatNode = one('#flat');
            var flatText = one('#flattxt');
            var scopeText = one('#scopetxt');

            if (scopeNode) {
                scopeNode.setAttribute('width', (scopeWidth * scale).toFixed(2));
                scopeNode.setAttribute('height', ((scopeWidth / 2.35) * scale).toFixed(2));
                scopeNode.setAttribute('x', ((400 - (scopeWidth * scale)) / 2).toFixed(2));
                scopeNode.setAttribute('y', ((400 - ((scopeWidth / 2.35) * scale)) / 2).toFixed(2));
            }

            if (academyNode) {
                academyNode.setAttribute('width', (academyWidth * scale).toFixed(2));
                academyNode.setAttribute('height', ((scopeWidth / 2.35) * scale).toFixed(2));
                academyNode.setAttribute('x', ((400 - (academyWidth * scale)) / 2).toFixed(2));
                academyNode.setAttribute('y', ((400 - ((scopeWidth / 2.35) * scale)) / 2).toFixed(2));
            }

            if (flatNode) {
                flatNode.setAttribute('width', (flatWidth * scale).toFixed(2));
                flatNode.setAttribute('height', ((flatWidth / 1.85) * scale).toFixed(2));
                flatNode.setAttribute('x', ((400 - (flatWidth * scale)) / 2).toFixed(2));
                flatNode.setAttribute('y', ((400 - ((flatWidth / 1.85) * scale)) / 2).toFixed(2));
            }

            if (flatText) {
                flatText.setAttribute('x', (((400 - (flatWidth * scale)) / 2) + 6).toFixed(2));
                flatText.setAttribute('y', ((((400 - ((flatWidth / 1.85) * scale)) / 2) + ((flatWidth / 1.85) * scale)) - 6).toFixed(2));
            }

            if (scopeText) {
                scopeText.setAttribute('x', (((400 - (scopeWidth * scale)) / 2) + 6).toFixed(2));
                scopeText.setAttribute('y', ((((400 - ((scopeWidth / 2.35) * scale)) / 2)) + 18).toFixed(2));
            }
        });
    }

    function initScreenDesign() {
        var root = one('[data-calculator-page="screendesign"]');
        var button = one('#cal');

        if (! root || ! button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();

            var type = checkedValue('type', '35scope');
            var width = 1;
            var height = 1;

            switch (type) {
                case '35scope':
                    width = 2.35;
                    break;
                case '35flat':
                    width = 1.85;
                    break;
                case 'academy':
                    width = 1.37;
                    break;
                case '8mm':
                    width = 1.33;
                    break;
                case 'super8':
                    width = 1.36;
                    break;
                case '16scope':
                    width = 2.66;
                    break;
                case '8scope':
                    width = 2;
                    break;
                case 'wxga':
                    width = 16;
                    height = 9;
                    break;
                case 'user':
                    width = numberValue('#param1', 2.4);
                    height = numberValue('#param2', 1);
                    break;
            }

            var screenWidth = numberValue('#screen_width', 8);
            var border = one('#border');
            var screen = one('#screen');

            if (width < height) {
                setText('screen_area_width', '0 เมตร');
                setText('screen_area_height', '0 เมตร');
                return;
            }

            var boundary = 400 * 0.7;
            var borderWidth = boundary * 1.2;
            var borderHeight = borderWidth / (width / height);
            var screenInnerWidth = borderWidth * 0.9;
            var screenInnerHeight = screenInnerWidth / (width / height);
            var offsetX = (borderWidth - screenInnerWidth) / 2;
            var offsetY = (borderHeight - screenInnerHeight) / 2;

            if (border) {
                border.setAttribute('width', borderWidth.toFixed(2));
                border.setAttribute('height', borderHeight.toFixed(2));
            }

            if (screen) {
                screen.setAttribute('x', (45 + offsetX).toFixed(2));
                screen.setAttribute('y', (120 + offsetY).toFixed(2));
                screen.setAttribute('width', screenInnerWidth.toFixed(2));
                screen.setAttribute('height', screenInnerHeight.toFixed(2));
            }

            setText('screen_area_width', meters(screenWidth));
            setText('screen_area_height', meters(screenWidth / (width / height)));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initThrowSelector();
        initThrowCalculator();
        initLensSimulation();
        initScreenDesign();
    });
})();
