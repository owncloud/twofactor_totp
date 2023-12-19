<?php
/**
 * @copyright Copyright (c) 2023, ownCloud GmbH
 * @license AGPL-3.0
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

namespace OCA\TwoFactor_Totp\Tests\Command;

use OCP\IUser;
use OCP\IUserManager;
use OCA\TwoFactor_Totp\Command\DeleteSecret;
use OCA\TwoFactor_Totp\Db\TotpSecretMapper;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class DeleteRedundantSecretsCommandTest
 */
class DeleteSecretTest extends TestCase {
	/** @var TotpSecretMapper */
	private $mapper;

	/** @var IUserManager */
	private $userManager;

	/** @var CommandTester */
	private $commandTester;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(TotpSecretMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$command = new DeleteSecret($this->mapper, $this->userManager);
		$this->commandTester = new CommandTester($command);
	}

	public function testCommandNoParams() {
		$this->commandTester->execute([]);
		$this->assertSame(1, $this->commandTester->getStatusCode());
	}

	public function testCommandOneUser() {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');

		$this->userManager->method('get')
			->will($this->returnValueMap([
				['user1', $user1],
			]));

		$this->mapper->expects($this->once())
			->method('deleteSecretsByUserId');

		$this->commandTester->execute(['uids' => ['user1']]);
		$this->assertSame(0, $this->commandTester->getStatusCode());
	}

	public function testCommandMultipleUsers() {
		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');

		$this->userManager->method('get')
			->will($this->returnValueMap([
				['user1', $user1],
				['user2', $user2],
			]));

		$this->mapper->expects($this->exactly(2))
			->method('deleteSecretsByUserId');

		$this->commandTester->execute(['uids' => ['user1', 'user2']]);
		$this->assertSame(0, $this->commandTester->getStatusCode());
	}

	public function testCommandAllUsers() {
		$this->mapper->expects($this->once())
			->method('deleteAllSecrets');

		$this->commandTester->execute(['--all' => null]);
		$this->assertSame(0, $this->commandTester->getStatusCode());
	}
}
