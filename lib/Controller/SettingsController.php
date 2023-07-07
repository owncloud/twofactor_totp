<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\TwoFactor_Totp\Controller;

use OCA\TwoFactor_Totp\Service\ITotp;
use OCA\TwoFactor_Totp\Service\OtpGen;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {
	/** @var ITotp */
	private $totp;

	/** @var IUserSession */
	private $userSession;

	/** @var OtpGen */
	private $otpGen;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param ITotp $totp
	 * @param OtpGen $otpGen
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserSession $userSession,
		ITotp $totp,
		OtpGen $otpGen
	) {
		parent::__construct($appName, $request);
		$this->userSession = $userSession;
		$this->totp = $totp;
		$this->otpGen = $otpGen;
	}

	/**
	 * @NoAdminRequired
	 * @return array
	 */
	public function state() {
		$user = $this->userSession->getUser();
		return [
			'enabled' => $this->totp->hasSecret($user) && $this->totp->isVerified($user),
		];
	}

	/**
	 * @NoAdminRequired
	 * @param bool $state
	 * @return array
	 */
	public function enable($state) {
		$user = $this->userSession->getUser();
		if ($state) {
			$secret = $this->totp->createSecret($user);

			return [
				'enabled' => true,
				'secret' => $secret,
				'qr' => $this->otpGen->generateOtpQR($this->userSession->getUser(), $secret),
			];
		}

		$this->totp->deleteSecret($user);
		return [
			'enabled' => false,
		];
	}

	/**
	 * @NoAdminRequired
	 * @param string $challenge
	 * @return array
	 */
	public function verifyNewSecret($challenge) {
		$user = $this->userSession->getUser();
		return [
			'verified' => $this->totp->verifySecret($user, $challenge)
		];
	}
}
