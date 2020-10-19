<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddUserTokenTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("user_token", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'id']);
        $table->addColumn('verification_token', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'user_id']);
        $table->addColumn('verification_token_created', 'datetime', ['null' => true, 'after' => 'verification_token']);
        $table->addColumn('verification_token_expires', 'datetime', ['null' => true, 'after' => 'verification_token_created']);
        $table->addColumn('password_reset_token', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'verification_token_expires']);
        $table->addColumn('password_reset_token_created', 'datetime', ['null' => true, 'after' => 'password_reset_token']);
        $table->addColumn('password_reset_token_expires', 'datetime', ['null' => true, 'after' => 'password_reset_token_created']);
        $table->save();
        $table->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])->save();
    }
}
