<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddUserTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("user", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('username', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('email', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'username']);
        $table->addColumn('password', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'email']);
        $table->addColumn('hashed_password', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'password']);
        $table->addColumn('role', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 5, 'after' => 'hashed_password']);
        $table->addColumn('local', 'string', ['null' => true, 'limit' => 45, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'role']);
        $table->addColumn('hard_banned', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'local']);
        $table->addColumn('email_verified', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'hard_banned']);
        $table->addColumn('uuid', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'email_verified']);
        $table->save();
        $table->addIndex(['email'], ['name' => "email", 'unique' => true])->save();
        $table->addIndex(['username'], ['name' => "username", 'unique' => true])->save();
        $table->addIndex(['email','username'], ['name' => "email_username", 'unique' => true])->save();
        $table->addIndex(['uuid','id'], ['name' => "uuid_id", 'unique' => true])->save();
        $table->addIndex(['uuid'], ['name' => "uuid", 'unique' => false])->save();
    }
}
