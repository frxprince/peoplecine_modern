(function () {
    function query(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function queryAll(selector, scope) {
        return Array.from((scope || document).querySelectorAll(selector));
    }

    function numericValue(element, fallback) {
        var raw = element ? parseFloat(element.value) : NaN;

        if (! Number.isFinite(raw)) {
            return fallback;
        }

        return raw;
    }

    function checkedValue(name, fallback) {
        var selected = query('input[name="' + name + '"]:checked');

        return selected ? selected.value : fallback;
    }

    function formatMeters(value) {
        return value.toFixed(2) + ' เมตร';
    }

    function formatInches(value) {
        return value.toFixed(2) + ' นิ้ว';
    }

    function formatMillimeters(value) {
        return value.toFixed(2) + ' มิลลิเมตร';
    }

    function setText(id, value) {
        var element = query('#' + id);

        if (element) {
            element.textContent = value;
        }
    }

    function setHidden(id, hidden) {
        var element = query('#' + id);

        if (element) {
            element.hidden = hidden;
        }
    }

    function calculateThrow() {
        var root = query('[data-calculator-page="throw"]');

        if (! root) {
            return;
        }

        var gateSize = 0.838;
        var screenType = root.dataset.throwScreen || 'scope';
        var printFormat = root.dataset.throwPrintFormat || 'scope';
        var focal = numericValue(query('#throw-focal'), 2.4) * parseFloat(checkedValue('throw-focal-unit', '1'));
        var widthMeters = numericValue(query('#throw-width'), 7) / parseFloat(checkedValue('throw-width-unit', '1'));
        var throwDistance = 0;
        var mainHeight = 0;
        var secondaryLabel = '';
        var secondaryValue = '';
        var matchedLens = '';
        var matchedVisible = false;
        var secondaryVisible = false;

        if (screenType === 'scope') {
            if (printFormat === 'scope' || printFormat === 'both') {
                throwDistance = (((((widthMeters * 12 * 3.2808399) / gateSize) + 1) * focal) / 12) / 2;
                mainHeight = widthMeters / 2.35;
                secondaryLabel = 'ความกว้างภาพตัดซีนที่ได้';
                secondaryValue = formatMeters(mainHeight * 1.85);

                if (printFormat === 'both') {
                    var recommendFlatLens = focal * 0.6371428;
                    matchedLens = formatInches(recommendFlatLens) + ' (' + formatMillimeters(recommendFlatLens / 0.03937008) + ')';
                    matchedVisible = true;
                    secondaryVisible = true;
                }
            } else {
                var scopeScreenHeight = widthMeters / 2.35;
                var flatWidthOnScope = scopeScreenHeight * 1.85;

                throwDistance = (((((flatWidthOnScope * 12 * 3.2808399) / gateSize) + 1) * focal) / 12);
                mainHeight = scopeScreenHeight;
                secondaryLabel = 'ความกว้างภาพตัดซีนที่ได้';
                secondaryValue = formatMeters(flatWidthOnScope);
                secondaryVisible = true;
            }
        } else {
            if (printFormat === 'scope') {
                throwDistance = (((((widthMeters * 12 * 3.2808399) / gateSize) + 1) * focal) / 12) / 2;
                mainHeight = widthMeters / 1.85;
                secondaryLabel = 'ความสูงภาพสโคปที่ฉายได้';
                secondaryValue = formatMeters(widthMeters / 2.35);
                secondaryVisible = true;
            } else {
                throwDistance = (((((widthMeters * 12 * 3.2808399) / gateSize) + 1) * focal) / 12);
                mainHeight = widthMeters / 1.85;

                if (printFormat === 'both') {
                    var recommendScopeLens = focal * 2;
                    secondaryLabel = 'ความสูงภาพสโคปที่ฉายได้';
                    secondaryValue = formatMeters(widthMeters / 2.35);
                    matchedLens = formatInches(recommendScopeLens) + ' (' + formatMillimeters(recommendScopeLens / 0.03937008) + ')';
                    matchedVisible = true;
                    secondaryVisible = true;
                }
            }
        }

        setText('throw-result-width', formatMeters(widthMeters));
        setText('throw-result-height', formatMeters(mainHeight));
        setText('throw-result-distance', formatMeters(throwDistance * 0.3048));
        setText('throw-distance-label', 'ระยะฉาย ' + formatMeters(throwDistance * 0.3048));
        setText('throw-secondary-label', secondaryLabel);
        setText('throw-secondary-value', secondaryValue);
        setText('throw-match-value', matchedLens);
        setHidden('throw-secondary-wrap', !secondaryVisible);
        setHidden('throw-match-wrap', !matchedVisible);

        var primaryFrame = query('#throw-preview-primary');
        var secondaryFrame = query('#throw-preview-secondary');
        var primaryRatio = screenType === 'scope' ? 2.35 : 1.85;
        var primaryHeight = 140 / primaryRatio;
        var primaryY = 110 - (primaryHeight / 2);

        if (primaryFrame) {
            primaryFrame.setAttribute('y', primaryY.toFixed(2));
            primaryFrame.setAttribute('height', primaryHeight.toFixed(2));
        }

        if (secondaryFrame) {
            if (secondaryVisible) {
                var secondaryRatio = screenType === 'scope' && printFormat !== 'flat' ? 1.85 : 2.35;
                var secondaryHeight = 140 / secondaryRatio;
                var secondaryY = 110 - (secondaryHeight / 2);

                secondaryFrame.removeAttribute('hidden');
                secondaryFrame.setAttribute('y', secondaryY.toFixed(2));
                secondaryFrame.setAttribute('height', secondaryHeight.toFixed(2));
            } else {
                secondaryFrame.setAttribute('hidden', 'hidden');
            }
        }
    }

    function calculateLensSimulation() {
        var root = query('[data-calculator-page="lenssim"]');

        if (! root) {
            return;
        }

        var focalScope = numericValue(query('#lenssim-scope'), 2.4) * parseFloat(checkedValue('lenssim-scope-unit', '1'));
        var focalFlat = numericValue(query('#lenssim-flat'), 2.4) * parseFloat(checkedValue('lenssim-flat-unit', '1'));
        var scopeWidth = 100;
        var flatWidth = ((focalScope / 2) / focalFlat) * scopeWidth;
        var maxHeight = Math.max(scopeWidth / 2.35, flatWidth / 1.85);
        var maxWidth = Math.max(scopeWidth, flatWidth);
        var scaleRatio = 320 / Math.max(maxHeight, maxWidth);
        var academyWidth = scopeWidth / 2;
        var scopeHeight = (scopeWidth / 2.35) * scaleRatio;
        var flatHeight = (flatWidth / 1.85) * scaleRatio;
        var scopeY = 44;
        var flatY = 44 + ((scopeHeight - flatHeight) / 2) + 48;
        var scopeWidthScaled = scopeWidth * scaleRatio;
        var academyWidthScaled = academyWidth * scaleRatio;
        var flatWidthScaled = flatWidth * scaleRatio;
        var scopeFrame = query('#lenssim-scope-frame');
        var academyFrame = query('#lenssim-academy-frame');
        var flatFrame = query('#lenssim-flat-frame');

        if (scopeFrame) {
            scopeFrame.setAttribute('x', ((420 - scopeWidthScaled) / 2).toFixed(2));
            scopeFrame.setAttribute('y', scopeY.toFixed(2));
            scopeFrame.setAttribute('width', scopeWidthScaled.toFixed(2));
            scopeFrame.setAttribute('height', scopeHeight.toFixed(2));
        }

        if (academyFrame) {
            academyFrame.setAttribute('x', ((420 - academyWidthScaled) / 2).toFixed(2));
            academyFrame.setAttribute('y', scopeY.toFixed(2));
            academyFrame.setAttribute('width', academyWidthScaled.toFixed(2));
            academyFrame.setAttribute('height', scopeHeight.toFixed(2));
        }

        if (flatFrame) {
            flatFrame.setAttribute('x', ((420 - flatWidthScaled) / 2).toFixed(2));
            flatFrame.setAttribute('y', flatY.toFixed(2));
            flatFrame.setAttribute('width', flatWidthScaled.toFixed(2));
            flatFrame.setAttribute('height', flatHeight.toFixed(2));
        }

        setText('lenssim-flat-width', flatWidth.toFixed(2) + ' หน่วย');
    }

    function screenRatio() {
        var type = checkedValue('screendesign-type', '35scope');

        switch (type) {
            case '35scope':
                return { width: 2.35, height: 1 };
            case '35flat':
                return { width: 1.85, height: 1 };
            case 'academy':
                return { width: 1.37, height: 1 };
            case '8mm':
                return { width: 1.33, height: 1 };
            case 'super8':
                return { width: 1.36, height: 1 };
            case '16scope':
                return { width: 2.66, height: 1 };
            case '8scope':
                return { width: 2, height: 1 };
            case 'wxga':
                return { width: 16, height: 9 };
            case 'user':
                return {
                    width: numericValue(query('#screendesign-ratio-width'), 2.4),
                    height: numericValue(query('#screendesign-ratio-height'), 1),
                };
            default:
                return { width: 2.35, height: 1 };
        }
    }

    function calculateScreenDesign() {
        var root = query('[data-calculator-page="screendesign"]');

        if (! root) {
            return;
        }

        var ratio = screenRatio();
        var widthMeters = numericValue(query('#screendesign-width'), 8);

        if (ratio.width <= 0 || ratio.height <= 0) {
            return;
        }

        var heightMeters = widthMeters / (ratio.width / ratio.height);
        var screenWidth = 330 * 0.9;
        var screenHeight = screenWidth / (ratio.width / ratio.height);
        var offsetX = (330 - screenWidth) / 2;
        var offsetY = (230 - screenHeight) / 2;
        var screen = query('#screendesign-screen');

        if (screen) {
            screen.setAttribute('x', (45 + offsetX).toFixed(2));
            screen.setAttribute('y', (70 + offsetY).toFixed(2));
            screen.setAttribute('width', screenWidth.toFixed(2));
            screen.setAttribute('height', screenHeight.toFixed(2));
        }

        setText('screendesign-result-width', formatMeters(widthMeters));
        setText('screendesign-result-height', formatMeters(heightMeters));
        setText('screendesign-result-ratio', ratio.width.toFixed(2) + ':' + ratio.height.toFixed(2));
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (query('[data-calculator-page="throw-selector"]')) {
            var nextButton = query('#throw-mode-next');

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    var screen = checkedValue('throw-screen', 'scope');
                    var printFormat = checkedValue('throw-print-format', 'scope');

                    window.location.href = '/calculator/throw/' + screen + '?print_format=' + encodeURIComponent(printFormat);
                });
            }
        }

        if (query('[data-calculator-page="throw"]')) {
            var throwButton = query('#throw-calculate');

            if (throwButton) {
                throwButton.addEventListener('click', calculateThrow);
            }

            calculateThrow();
        }

        if (query('[data-calculator-page="lenssim"]')) {
            var lensButton = query('#lenssim-calculate');

            if (lensButton) {
                lensButton.addEventListener('click', calculateLensSimulation);
            }

            calculateLensSimulation();
        }

        if (query('[data-calculator-page="screendesign"]')) {
            var screenButton = query('#screendesign-calculate');

            if (screenButton) {
                screenButton.addEventListener('click', calculateScreenDesign);
            }

            queryAll('input[name="screendesign-type"]').forEach(function (radio) {
                radio.addEventListener('change', calculateScreenDesign);
            });

            calculateScreenDesign();
        }
    });
})();
