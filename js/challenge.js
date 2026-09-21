/**
 * Copy-to-clipboard for the TOTP secret shown on the two-factor challenge page.
 *
 * The secret is only rendered for users who have not verified one yet, so on a normal
 * challenge page there is nothing to wire up and this is a no-op. No dependency on jQuery
 * or OC is used here, because the login page is not the place to rely on either, and the
 * labels are rendered by the template - the app's l10n bundle is not loaded on the login
 * page, so t() would not be able to translate them.
 *
 * Clicking the button always selects the whole key, and additionally puts it on the
 * clipboard wherever navigator.clipboard exists, which is every secure context - so every
 * deployment that serves the login page over HTTPS, plus localhost. There is deliberately
 * no document.execCommand('copy') fallback for the remaining plain-HTTP case: that command
 * copies from the focused text control rather than from the document selection, and the
 * challenge field is focused on arrival because of its "autofocus". Measured in Chrome 153
 * it returned true while the clipboard kept its previous contents, i.e. it reported success
 * over a stale clipboard. Working around that means blurring and refocusing across engines
 * that behave differently and that CI does not exercise, for a case that only arises on a
 * login page served without TLS. Selecting the key so it can be copied with Ctrl+C covers
 * it without claiming anything untrue.
 */
(function () {
    'use strict';

    /**
     * Make the secret the current document selection.
     *
     * This is both the visible confirmation of what the button acted on and, where the
     * clipboard is unavailable or refuses the write, what lets the key be copied by hand.
     */
    function selectSecret(secret) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(secret);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function init() {
        var secret = document.getElementById('totp-secret');
        var button = document.getElementById('totp-copy-secret');
        if (secret === null || button === null) {
            return;
        }

        var initialLabel = button.textContent;
        var copiedLabel = button.getAttribute('data-copied-label');
        var restoreTimer = null;

        var confirmCopied = function () {
            button.textContent = copiedLabel;
            window.clearTimeout(restoreTimer);
            restoreTimer = window.setTimeout(function () {
                button.textContent = initialLabel;
            }, 3000);
        };

        // Without JavaScript the button would do nothing, so the template renders it
        // hidden and it is only revealed here.
        button.removeAttribute('hidden');

        button.addEventListener('click', function () {
            selectSecret(secret);

            // undefined outside a secure context
            if (!window.navigator.clipboard || !window.navigator.clipboard.writeText) {
                return;
            }
            window.navigator.clipboard.writeText(secret.textContent).then(confirmCopied, function () {
                // Refused - the label is left alone rather than claiming a copy that did
                // not happen, and the key stays selected.
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
