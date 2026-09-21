/**
 * Copy-to-clipboard for the TOTP secret shown on the two-factor challenge page.
 *
 * The secret is only rendered for users who have not verified one yet, so on a normal
 * challenge page there is nothing to wire up and this is a no-op. No dependency on jQuery
 * or OC is used here, because the login page is not the place to rely on either, and the
 * labels are rendered by the template - the app's l10n bundle is not loaded on the login
 * page, so t() would not be able to translate them.
 */
(function () {
    'use strict';

    /**
     * Make the secret the current document selection.
     *
     * This is the copy source for the execCommand fallback, and when copying is refused
     * outright it at least leaves the key selected so it can be copied by hand.
     */
    function selectSecret(secret) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(secret);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    /**
     * Copy the current selection with the legacy command, for insecure contexts.
     *
     * The challenge field carries "autofocus", and a browser that does not focus a
     * button when it is clicked - Safari and Firefox on macOS - leaves it focused.
     * Those engines then resolve the copy command against the focused text control's
     * own, empty, selection instead of the document selection, so the field has to be
     * blurred first. Focus is only handed back when the copy actually succeeded: on
     * failure the selection is the user's remaining way to get at the key, and
     * focusing the field again would drop it.
     *
     * @return {boolean} whether the secret reached the clipboard
     */
    function copyWithExecCommand() {
        var focused = document.activeElement;
        if (focused !== null && focused !== document.body && typeof focused.blur === 'function') {
            focused.blur();
        }

        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (e) {
            // execCommand throws rather than returning false when the command is
            // disabled - the same outcome as a refusal.
        }

        if (copied && focused !== null && typeof focused.focus === 'function') {
            focused.focus();
        }
        return copied;
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

            // navigator.clipboard is undefined outside a secure context, which is why
            // the execCommand fallback is still needed.
            if (window.navigator.clipboard && window.navigator.clipboard.writeText) {
                window.navigator.clipboard.writeText(secret.textContent).then(confirmCopied, function () {
                    // Copying was denied. Nothing is claimed, and the key stays selected.
                });
                return;
            }
            if (copyWithExecCommand()) {
                confirmCopied();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
