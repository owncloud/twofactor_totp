<?php declare(strict_types=1);
/**
 * ownCloud
 *
 * @author Hari Bhandari <hari@jankaritech.com>
 * @copyright Copyright (c) 2019 Hari Bhandari hari@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Page;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;

/**
 * Class VerificationPage
 *
 * @package Page
 */
class VerificationPage extends OwncloudPage {
	private $verificationFieldXpath = '//form//input[@name="challenge"]';
	private $verifySubmissionBtnXpath = '//form//button[@type="submit"]';
	private $errorTokenMessageXpath = '//div/span[contains(text(),"verifying the token")]';
	private $cancelOrLoginButtonXpath = '//a[@class="two-factor-cancel"]';
	// the form input is wrapped in a "grouptop" div as well, so the enrolment
	// block is identified by the QR code image it contains
	private $enrolmentBlockXpath = '//div[contains(@class,"grouptop")][.//img]';
	private $enrolmentQrCodeXpath = '//div[contains(@class,"grouptop")]//img';
	private $enrolmentSecretXpath = '//div[contains(@class,"grouptop")]//p/strong';
	private $enrolmentSecretId = 'totp-secret';
	private $enrolmentCopyButtonXpath = '//div[contains(@class,"grouptop")]//p/button[@id="totp-copy-secret"]';

	/**
	 * there is no reliable loading indicator on the verification page, so just wait for
	 * the verification field to be there.
	 *
	 * @param Session $session
	 * @param int $timeout_msec
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function waitTillPageIsLoaded(
		Session $session,
		int $timeout_msec = STANDARD_UI_WAIT_TIMEOUT_MILLISEC
	): void {
		$field = $this->waitTillElementIsNotNull(
			$this->verificationFieldXpath,
			$timeout_msec
		);
		$this->assertElementNotNull(
			$field,
			__METHOD__ . ' Field for adding verification code could not be found'
		);

		$this->waitForOutstandingAjaxCalls($session);
	}

	/**
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function addVerificationKey(string $key): void {
		$field = $this->waitTillElementIsNotNull($this->verificationFieldXpath);
		$field->setValue($key);
		$submit_btn = $this->find("xpath", $this->verifySubmissionBtnXpath);
		$submit_btn->click();
	}

	/**
	 *
	 * @return string
	 */
	public function getErrorMessage(): string {
		$errorMessageElement = $this->find(
			"xpath",
			$this->errorTokenMessageXpath
		);
		$this->assertElementNotNull(
			$errorMessageElement,
			__METHOD__ .
			" xpath $this->errorTokenMessageXpath" .
			" could not find token verification error message"
		);
		return $errorMessageElement->getText();
	}

	/**
	 *
	 * @return void
	 */
	public function cancelVerification(): void {
		$this->waitTillElementIsNotNull($this->cancelOrLoginButtonXpath);
		$cancel_btn = $this->find("xpath", $this->cancelOrLoginButtonXpath);
		$cancel_btn->click();
	}

	/**
	 *
	 * @return NodeElement|null
	 */
	public function isErrorMessagePresent(): ?NodeElement {
		return $this->find('xpath', $this->errorTokenMessageXpath);
	}

	/**
	 * Returns the enrolment QR code image in base64.
	 *
	 * The QR code is only rendered when the user has not verified a secret yet,
	 * which is the case when 2-factor auth is enforced for a user who has never
	 * configured the app.
	 *
	 * @return string
	 */
	public function getEnrolmentQRCode(): string {
		$image = $this->waitTillElementIsNotNull($this->enrolmentQrCodeXpath);
		$this->assertElementNotNull(
			$image,
			__METHOD__ . ' enrolment QR code not found on the verification page'
		);
		return $image->getAttribute("src");
	}

	/**
	 * Returns the enrolment block (QR code and secret), or null when the user
	 * has already verified a secret and does not need to enrol.
	 *
	 * @return NodeElement|null
	 */
	public function isEnrolmentBlockPresent(): ?NodeElement {
		return $this->find('xpath', $this->enrolmentBlockXpath);
	}

	/**
	 * Returns the enrolment secret displayed next to the QR code.
	 *
	 * It is displayed for users who cannot scan a QR code and have to type the
	 * secret into their TOTP app by hand.
	 *
	 * @return string
	 */
	public function getEnrolmentSecret(): string {
		$secret = $this->waitTillElementIsNotNull($this->enrolmentSecretXpath);
		$this->assertElementNotNull(
			$secret,
			__METHOD__ . ' enrolment secret not found on the verification page'
		);
		return \trim($secret->getText());
	}

	/**
	 * Returns the computed "user-select" of the enrolment secret.
	 *
	 * The login page sets "user-select: none" on both "#body-login p.info" and
	 * ".grouptop", and the secret sits inside both, so without the app's own
	 * stylesheet it cannot be selected - and therefore cannot be copied into a
	 * TOTP app.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getEnrolmentSecretUserSelect(): string {
		$secret = $this->waitTillElementIsNotNull($this->enrolmentSecretXpath);
		$this->assertElementNotNull(
			$secret,
			__METHOD__ . ' enrolment secret not found on the verification page'
		);
		// The element is looked up by id rather than reusing $secret because only a
		// computed style answers the question, and that needs script evaluation. The
		// prefixed property is read as well, because the browser the CI job drives is
		// old enough to expose only "-webkit-user-select".
		$userSelect = $this->getSession()->evaluateScript(
			'return (function (el) {' .
			' if (el === null) { return null; }' .
			' var style = window.getComputedStyle(el);' .
			' return style.getPropertyValue("user-select")' .
			' || style.getPropertyValue("-webkit-user-select");' .
			'})(document.getElementById("' . $this->enrolmentSecretId . '"));'
		);
		// only reachable if the xpath above and the id drift apart - reported here so
		// that the caller sees the cause instead of comparing against a stand-in value
		if ($userSelect === null) {
			throw new \Exception(
				__METHOD__ .
				" no element with id $this->enrolmentSecretId on the verification page"
			);
		}
		return $userSelect;
	}

	/**
	 * Clicks the button that copies the enrolment secret to the clipboard.
	 *
	 * @return void
	 */
	public function copyEnrolmentSecret(): void {
		// visibility, not mere presence: the button is rendered with the "hidden"
		// attribute and only revealed by challenge.js on DOMContentLoaded, so waiting
		// for the node alone would race the script and click a hidden element
		$this->waitTillXpathIsVisible($this->enrolmentCopyButtonXpath)->click();
	}

	/**
	 * Replaces navigator.clipboard with a stub that records what it was asked to write.
	 *
	 * The browser the acceptance tests drive is reached over plain HTTP, which is not a
	 * secure context, so navigator.clipboard does not exist there and the copy path would
	 * never run. challenge.js only looks the API up when the button is clicked, so
	 * installing the stub after the page has loaded is enough. This keeps the assertion
	 * deterministic and needs neither HTTPS nor a clipboard permission.
	 *
	 * executeScript, not evaluateScript: the latter prepends "return " to a script that
	 * does not already start with it, which would make everything after the first
	 * statement here unreachable and leave the stub uninstalled.
	 *
	 * @return void
	 */
	public function stubClipboard(): void {
		$this->getSession()->executeScript(
			'window.totpClipboardWrites = [];' .
			' Object.defineProperty(window.navigator, "clipboard", {' .
			' configurable: true,' .
			' value: {' .
			' writeText: function (text) {' .
			' window.totpClipboardWrites.push(text);' .
			' return Promise.resolve();' .
			' }' .
			' }' .
			' });'
		);
	}

	/**
	 * Returns everything the stubbed clipboard was asked to write, in order.
	 *
	 * @return array
	 */
	public function getStubbedClipboardWrites(): array {
		return $this->getSession()->evaluateScript(
			'return window.totpClipboardWrites || [];'
		);
	}

	/**
	 * Returns the text the browser currently has selected.
	 *
	 * @return string
	 */
	public function getSelectedText(): string {
		return \trim(
			$this->getSession()->evaluateScript('return window.getSelection().toString();')
		);
	}
}
