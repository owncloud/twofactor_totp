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

namespace OCA\TwoFactor_Totp\Command;

use OCA\TwoFactor_Totp\Db\TotpSecretMapper;
use OCP\IUserManager;
use OCP\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteSecret extends Command {
	/** @var TotpSecretMapper */
	private $secretMapper;

	/** @var IUserManager */
	private $userManager;

	public function __construct(TotpSecretMapper $secretMapper, IUserManager $userManager) {
		parent::__construct();
		$this->secretMapper = $secretMapper;
		$this->userManager = $userManager;
	}

	protected function configure() {
		$this->setName('twofactor_totp:delete-secret')
			->setDescription('Delete the secret of a user')
			->addArgument(
				'uids',
				InputArgument::IS_ARRAY,
				'The list of users whose secrets must be deleted'
			)->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'Delete the secrets of all users'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uids = $input->getArgument('uids');
		if (empty($uids) && !$input->getOption('all')) {
			$output->writeln('<error>Either --all or at least a user id must be provided</error>');
			return 1;
		}

		if (!empty($uids)) {
			foreach ($uids as $uid) {
				$this->secretMapper->deleteSecretsByUserId($uid);
			}
		} else {
			$this->userManager->callForSeenUsers(function (IUser $user) {
				$this->secretMapper->deleteSecretsByUserId($user->getUID());
			});
		}
		return 0;
	}
}
