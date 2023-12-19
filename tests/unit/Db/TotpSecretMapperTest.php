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

namespace OCA\Twofactor_Totp\Tests\Db;

use OCA\TwoFactor_Totp\Db\TotpSecret;
use OCA\TwoFactor_Totp\Db\TotpSecretMapper;
use OCP\IDBConnection;
use Test\TestCase;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class TotpSecretMapperTest extends TestCase {
	use UserTrait;

	/** @var IDBConnection */
	private $db;

	/** @var TotpSecretMapper */
	private $mapper;

	/** @var string  */
	private $dbTable = 'twofactor_totp_secrets';

	protected function setUp(): void {
		parent::setUp();
		$this->db = \OC::$server->getDatabaseConnection();
		$this->mapper = new TotpSecretMapper($this->db);
		$this->createUser('user1');
		$this->createUser('user2');
		$this->mapper->insert(TotpSecret::fromParams([
			'userId' => 'user1',
			'secret' => 'test',
			'verified' => false
		]));
	}

	protected function tearDown(): void {
		parent::tearDown();
		$query = $this->db->getQueryBuilder()->delete($this->dbTable);
		$query->execute();
	}

	/**
	 */
	public function testGetNonExistSecret() {
		$this->expectException(\OCP\AppFramework\Db\DoesNotExistException::class);

		$user = \OC::$server->getUserManager()->get('user2');
		$this->mapper->getSecret($user);
	}

	public function testGetSecret() {
		$user = \OC::$server->getUserManager()->get('user1');
		$secret = $this->mapper->getSecret($user);
		$this->assertEquals($user->getUID(), $secret->getUserId());
	}

	public function testSetAllSecretsVerificationStatus() {
		$this->mapper->insert(TotpSecret::fromParams([
			'userId' => 'user2',
			'secret' => 'test',
			'verified' => false
		]));
		$user1 = \OC::$server->getUserManager()->get('user1');
		$user2 = \OC::$server->getUserManager()->get('user2');
		$secret1 = $this->mapper->getSecret($user1);
		$this->assertEquals(false, (boolean)$secret1->getVerified());
		$secret2 = $this->mapper->getSecret($user2);
		$this->assertEquals(false, (boolean)$secret2->getVerified());

		$this->mapper->setAllSecretsVerificationStatus(true);
		$secret1 = $this->mapper->getSecret($user1);
		$this->assertEquals(true, (boolean)$secret1->getVerified());
		$secret2 = $this->mapper->getSecret($user2);
		$this->assertEquals(true, (boolean)$secret2->getVerified());

		$this->mapper->setAllSecretsVerificationStatus(false);
		$secret1 = $this->mapper->getSecret($user1);
		$this->assertEquals(false, (boolean)$secret1->getVerified());
		$secret2 = $this->mapper->getSecret($user2);
		$this->assertEquals(false, (boolean)$secret2->getVerified());
	}

	/**
	 */
	public function testDeleteSecretsByUserId() {
		$this->expectException(\OCP\AppFramework\Db\DoesNotExistException::class);

		$user = \OC::$server->getUserManager()->get('user1');
		$secret = $this->mapper->getSecret($user);
		$this->assertEquals($user->getUID(), $secret->getUserId());

		$this->assertSame(1, $this->mapper->deleteSecretsByUserId('user1'));
		$this->mapper->getSecret($user);
	}

	public function testGetAllSecrets() {
		$secrets1 = $this->mapper->getAllSecrets();
		$this->assertCount(1, $secrets1);
		$this->mapper->insert(TotpSecret::fromParams([
			'userId' => 'user3',
			'secret' => 'test',
			'verified' => false
		]));
		$secrets2 = $this->mapper->getAllSecrets();
		$this->assertCount(2, $secrets2);
	}

	public function testDeleteAllSecrets() {
		// only 1 existing secret in the DB, which will be deleted
		$this->assertSame(1, $this->mapper->deleteAllSecrets());
		// no secrets in the DB
		$this->assertSame(0, $this->mapper->deleteAllSecrets());
	}
}
