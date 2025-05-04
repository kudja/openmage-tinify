;(function () {
    function detectWebpSupport(callback) {
        if (typeof window.supportsWebp !== 'undefined') {
            callback(window.supportsWebp);
            return;
        }

        const img = new Image();
        img.onload = function () {
            window.supportsWebp = (img.width > 0) && (img.height > 0);
            callback(window.supportsWebp);
        };
        img.onerror = function () {
            window.supportsWebp = false;
            callback(false);
        };
        img.src = 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA';
    }

    function replaceWebpWithOriginal() {
        console.log('WebP support not detected, replacing .webp with original images');

        window.Tinify = window.Tinify || {};
        window.Tinify.tags = window.Tinify.tags || ['img', 'source', 'a', 'link'];
        window.Tinify.attributes = window.Tinify.attributes || ['src', 'srcset', 'data-src', 'data-srcset', 'href', 'imgsrcset'];

        const elements = document.querySelectorAll(window.Tinify.tags.join(', '));
        const pattern = /\.(jpe?g|png)\.webp(\b|$)/gi;

        elements.forEach(el => {
            window.Tinify.attributes.forEach(attr => {
                if (!el.hasAttribute(attr)) return;

                const originalValue = el.getAttribute(attr);
                if (!originalValue) return;

                const newValue = originalValue.replace(pattern, '.$1');
                if (newValue !== originalValue) {
                    el.setAttribute(attr, newValue);
                }
            });
        });
    }

    detectWebpSupport(supports => {
        if (!supports) {
            replaceWebpWithOriginal();
        }
    });
})();
