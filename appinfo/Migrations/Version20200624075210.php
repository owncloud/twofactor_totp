<?php
namespace OCA\twofactor_totp\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version20200624075210 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}twofactor_totp_secrets")) {
			$table = $schema->createTable("{$prefix}twofactor_totp_secrets");
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('secret', 'text', [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user_id'], 'totp_secrets_user_id');
		}
		$table = $schema->getTable("{$prefix}twofactor_totp_secrets");
		if (!$table->hasColumn('verified')) {
			$table->addColumn('verified', 'boolean', [
				'default' => false,
			]);
		}
		if (!$table->hasColumn('last_validated_key')) {
			$table->addColumn('last_validated_key', 'string', [
				'notnull' => true,
				'length' => 32,
				'default' => '',
			]);
		}
	}
}
