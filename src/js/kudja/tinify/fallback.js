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

    function replaceWebpWithOriginal(root) {
        window.Tinify = window.Tinify || {};
        const tags = window.Tinify.tags || ['img', 'source', 'a', 'link'];
        const attributes = window.Tinify.attributes || ['src', 'srcset', 'data-src', 'data-srcset', 'href', 'imgsrcset'];
        const pattern = /\.(jpe?g|png)\.webp(\b|$)/gi;

        const elements = root.querySelectorAll(tags.join(', '));

        elements.forEach(el => {
            if (el.hasAttribute('data-webp-processed')) return;
            let replaced = false;

            attributes.forEach(attr => {
                if (!el.hasAttribute(attr)) return;
                const val = el.getAttribute(attr);
                if (!val) return;
                const newVal = val.replace(pattern, '.$1');
                if (newVal !== val) {
                    el.setAttribute(attr, newVal);
                    replaced = true;
                }
            });

            if (replaced) {
                el.setAttribute('data-webp-processed', 'true');
            }
        });
    }

    function observeDomChanges() {
        const ignoredTags = ['SCRIPT', 'STYLE', 'IFRAME'];

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType !== 1) return; // not an element
                    if (ignoredTags.includes(node.tagName)) return;
                    replaceWebpWithOriginal(node);
                });
            });
        });

        observer.observe(document.documentElement || document.body, {
            childList: true,
            subtree: true
        });
    }

    function interceptImageSrcAssignment() {
        const pattern = /(\.jpe?g|\.png)\.webp(\?.*)?$/i;
        const descriptor = Object.getOwnPropertyDescriptor(Image.prototype, 'src');
        if (!descriptor || !descriptor.configurable) return;

        Object.defineProperty(Image.prototype, 'src', {
            set(value) {
                if (value && pattern.test(value)) {
                    value = value.replace(pattern, '$1$2');
                }
                return descriptor.set.call(this, value);
            },
            get() {
                return descriptor.get.call(this);
            },
            configurable: true
        });
    }

    window.replaceWebpWithOriginal = replaceWebpWithOriginal;

    detectWebpSupport(supports => {
        if (!supports) {
            console.log('WebP support not detected, replacing .webp with original images');
            replaceWebpWithOriginal(document);
            observeDomChanges();
            interceptImageSrcAssignment();
        }
    });
})();
