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

namespace OCA\TwoFactor_Totp\Unit\Controller;

use OCA\TwoFactor_Totp\Controller\SettingsController;
use OCA\TwoFactor_Totp\Service\OtpGen;
use Test\TestCase;

class SettingsControllerTest extends TestCase {
	private $request;
	private $userSession;
	private $totp;
	private $otpGen;

	/** @var SettingsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock('\OCP\IRequest');
		$this->userSession = $this->createMock('\OCP\IUserSession');
		$this->totp = $this->createMock('\OCA\TwoFactor_Totp\Service\ITotp');
		$this->otpGen = $this->createMock(OtpGen::class);

		$this->controller = new SettingsController('twofactor_totp', $this->request, $this->userSession, $this->totp, $this->otpGen);
	}

	/**
	 * @dataProvider dataTestState
	 *
	 * @param boolean $hasSecret
	 * @param boolean $isVerified
	 * @param boolean $enabled
	 */
	public function testState($hasSecret, $isVerified, $enabled) {
		$user = $this->createMock('\OCP\IUser');
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->totp->expects($this->once())
			->method('hasSecret')
			->with($user)
			->will($this->returnValue($hasSecret));
		if ($hasSecret) {
			$this->totp->expects($this->once())
				->method('isVerified')
				->with($user)
				->will($this->returnValue($isVerified));
		}

		$expected = [
			'enabled' => $enabled,
		];

		$this->assertEquals($expected, $this->controller->state());
	}
	public function dataTestState() {
		return [
			[true, true, true],
			[false, false, false],
			[true, false, false],
			[false, true, false]
		];
	}

	public function testEnable() {
		$user = $this->createMock('\OCP\IUser');
		$this->userSession->method('getUser')
			->will($this->returnValue($user));
		$user->method('getCloudId')
			->will($this->returnValue('user@instance.com'));
		$this->totp->expects($this->once())
			->method('createSecret')
			->with($user)
			->will($this->returnValue('newsecret'));
		$this->otpGen->method('generateOtpQR')
			->willReturn('data:image/png;base64,abc123def456');

		$expected = [
			'enabled' => true,
			'secret' => 'newsecret',
			'qr' => 'data:image/png;base64,abc123def456',
		];

		$this->assertEquals($expected, $this->controller->enable(true));
	}

	public function testEnableDisable() {
		$user = $this->createMock('\OCP\IUser');
		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$this->totp->expects($this->once())
			->method('deleteSecret');

		$expected = [
			'enabled' => false,
		];

		$this->assertEquals($expected, $this->controller->enable(false));
	}
}
