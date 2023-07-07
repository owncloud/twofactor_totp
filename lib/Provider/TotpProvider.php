<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 *
 * Two-factor TOTP
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactor_Totp\Provider;

use OCA\TwoFactor_Totp\Service\ITotp;
use OCA\TwoFactor_Totp\Service\OtpGen;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IL10N;
use OCP\IUser;
use OCP\Template;

class TotpProvider implements IProvider {
	/** @var ITotp */
	private $totp;
	/** @var OtpGen */
	private $otpGen;

	/** @var IL10N */
	private $l10n;

	/**
	 * @param ITotp $totp
	 * @param IL10N $l10n
	 */
	public function __construct(ITotp $totp, OtpGen $otpGen, IL10N $l10n) {
		$this->totp = $totp;
		$this->otpGen = $otpGen;
		$this->l10n = $l10n;
	}

	/**
	 * Get unique identifier of this 2FA provider
	 *
	 * @return string
	 */
	public function getId() {
		return 'totp';
	}

	/**
	 * Get the display name for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return $this->l10n->t('Time-based One-time Password');
	}

	/**
	 * Get the description for selecting the 2FA provider
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->l10n->t('Authenticate with a TOTP app');
	}

	/**
	 * Get the template for rending the 2FA provider view
	 *
	 * @param IUser $user
	 * @return Template
	 */
	public function getTemplate(IUser $user) {
		$tmpl = new Template('twofactor_totp', 'challenge');
		if (!$this->isTwoFactorAuthEnabledForUser($user)) {
			// If 2-factor is enforced, the challenge page will be accessed
			// regardless of the user having configured the app or not.
			// If the user doesn't have the app configured, we need to show
			// the QR so the user is able to configured the app from the
			// challenge page. The QR won't be shown if the app is already
			// configured
			$this->totp->deleteSecret($user);
			$secret = $this->totp->createSecret($user);
			$tmpl->assign('isConfigured', false);
			$tmpl->assign('qr', $this->otpGen->generateOtpQR($user, $secret));
		} else {
			$tmpl->assign('isConfigured', true);
		}
		return $tmpl;
	}

	/**
	 * Verify the given challenge
	 *
	 * @param IUser $user
	 * @param string $challenge
	 */
	public function verifyChallenge(IUser $user, $challenge) {
		return $this->totp->validateKey($user, $challenge);
	}

	/**
	 * Decides whether 2FA is enabled and verified for the given user
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user) {
		return $this->totp->hasSecret($user) && $this->totp->isVerified($user);
	}
}
