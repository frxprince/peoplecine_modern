document.addEventListener('DOMContentLoaded', function () {
    var banner = document.querySelector('[data-cookie-consent-banner]');

    if (!banner) {
        return;
    }

    var getConsentCookie = function () {
        var prefix = 'peoplecine_cookie_consent=';
        var match = document.cookie
            .split(';')
            .map(function (item) { return item.trim(); })
            .find(function (item) { return item.indexOf(prefix) === 0; });

        if (!match) {
            return null;
        }

        return decodeURIComponent(match.substring(prefix.length));
    };

    var setConsentCookie = function (value) {
        var maxAgeSeconds = 60 * 60 * 24 * 180;
        var expires = new Date(Date.now() + (maxAgeSeconds * 1000)).toUTCString();
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';

        document.cookie = 'peoplecine_cookie_consent=' + encodeURIComponent(value)
            + '; Max-Age=' + maxAgeSeconds
            + '; Expires=' + expires
            + '; Path=/; SameSite=Lax'
            + secure;
    };

    if (getConsentCookie()) {
        banner.remove();
        return;
    }

    banner.querySelectorAll('[data-cookie-consent]').forEach(function (button) {
        button.addEventListener('click', function () {
            setConsentCookie(button.getAttribute('data-cookie-consent') || 'accepted');
            banner.remove();
        });
    });
});
