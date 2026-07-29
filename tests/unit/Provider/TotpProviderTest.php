<?php
/**
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

namespace OCA\TwoFactor_Totp\Tests\Provider;

use OCA\TwoFactor_Totp\Provider\TotpProvider;
use OCA\TwoFactor_Totp\Service\ITotp;
use OCA\TwoFactor_Totp\Service\OtpGen;
use OCP\IL10N;
use OCP\IUser;
use Test\TestCase;

/**
 * Class TotpTest
 */
class TotpProviderTest extends TestCase {
	/** @var ITotp | \PHPUnit\Framework\MockObject\MockObject  $totp */
	private $totp;
	/** @var OtpGen */
	private $otpGen;

	/** @var IL10N | \PHPUnit\Framework\MockObject\MockObject */
	private $l;

	/** @var IUser | \PHPUnit\Framework\MockObject\MockObject */
	private $user;

	/** @var TotpProvider $totpProvider */
	private $totpProvider;

	protected function setUp(): void {
		parent::setUp();

		$this->totp = $this->createMock(ITotp::class);
		$this->otpGen = $this->createMock(OtpGen::class);
		$this->l = $this->createMock(IL10N::class);
		$this->user = $this->createMock(IUser::class);

		$this->totpProvider = new TotpProvider($this->totp, $this->otpGen, $this->l);
	}

	public function testVerifyChallange() {
		$this->totp->expects($this->once())
			->method('validateKey')
			->with($this->user, '111111');
		$this->totpProvider->verifyChallenge($this->user, '111111');
	}

	/**
	 * An unverified secret means the user still has to enroll their TOTP app
	 * from the challenge page - which is the only page they get when 2FA is
	 * enforced. Both the QR code and the secret itself have to be handed to
	 * the template, so that users who cannot scan a QR code are still able to
	 * enroll.
	 */
	public function testGetTemplateShowsQrAndSecretForUnverifiedSecret() {
		$this->totp->method('getSecretInfo')
			->with($this->user)
			->willReturn(['secret' => 'MYTOTPSECRET', 'verified' => false]);
		$this->otpGen->method('generateOtpQR')
			->with($this->user, 'MYTOTPSECRET')
			->willReturn('data:image/png;base64,QRCODE');

		$page = $this->totpProvider->getTemplate($this->user)->fetchPage();

		$this->assertStringContainsString('data:image/png;base64,QRCODE', $page);
		$this->assertStringContainsString('MYTOTPSECRET', $page);
	}

	/**
	 * A verified secret means the app is already enrolled - neither the QR
	 * code nor the secret may be disclosed on the challenge page.
	 */
	public function testGetTemplateHidesSecretForVerifiedSecret() {
		$this->totp->method('getSecretInfo')
			->with($this->user)
			->willReturn(['secret' => 'MYTOTPSECRET', 'verified' => true]);
		$this->otpGen->expects($this->never())
			->method('generateOtpQR');

		$page = $this->totpProvider->getTemplate($this->user)->fetchPage();

		$this->assertStringNotContainsString('MYTOTPSECRET', $page);
	}
}
