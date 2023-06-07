<?php
/**
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

namespace OCA\TwoFactor_Totp\Tests\Service;

use OCA\TwoFactor_Totp\Service\OtpGen;
use OCP\Defaults;
use OCP\IUser;
use Test\TestCase;

/**
 * Class OtpGenTest
 */
class OtpGenTest extends TestCase {
	private $defaults;
	private $otpGen;

	protected function setUp(): void {
		$this->defaults = $this->createMock(Defaults::class);

		$this->otpGen = new OtpGen($this->defaults);
	}

	public function testGenerateOtpUrl() {
		$this->defaults->method('getName')->willReturn('myÑoduct');

		$user = $this->createMock(IUser::class);
		$user->method('getCloudId')->willReturn('user@prod.ttt');

		$expectedUrl = 'otpauth://totp/my%C3%91oduct%3Auser%40prod.ttt?secret=ABC321&issuer=my%C3%91oduct';
		$this->assertSame($expectedUrl, $this->otpGen->generateOtpUrl($user, 'ABC321'));
	}

	public function testGenerateOtpQR() {
		$this->defaults->method('getName')->willReturn('myÑoduct');

		$user = $this->createMock(IUser::class);
		$user->method('getCloudId')->willReturn('user@prod.ttt');

		$this->assertStringStartsWith('data:image/png;base64,', $this->otpGen->generateOtpQR($user, 'ABC321'));
	}
}
