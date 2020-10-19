<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddApiAppTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("api_app", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('name', 'string', ['null' => false, 'limit' => 100, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 10, 'after' => 'name']);
        $table->addColumn('access_token', 'string', ['null' => false, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'user_id']);
        $table->addColumn('created_at', 'datetime', ['null' => true, 'after' => 'access_token']);
        $table->addColumn('token_regenerated_at', 'datetime', ['null' => true, 'after' => 'created_at']);
        $table->addColumn('first_request', 'datetime', ['null' => true, 'after' => 'token_regenerated_at']);
        $table->addColumn('last_request', 'datetime', ['null' => true, 'after' => 'first_request']);
        $table->addColumn('rate_limit', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'last_request']);
        $table->addColumn('requests_spent', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'rate_limit']);
        $table->addColumn('requests_remaining', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'requests_spent']);
        $table->addColumn('limit_reached', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 4, 'after' => 'requests_remaining']);
        $table->addColumn('time_left_until_reset', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 15, 'after' => 'limit_reached']);
        $table->addColumn('deactivated', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'time_left_until_reset']);
        $table->addColumn('soft_banned', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'deactivated']);
        $table->addColumn('hard_banned', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'soft_banned']);
        $table->addColumn('soft_banned_at', 'datetime', ['null' => true, 'after' => 'hard_banned']);
        $table->save();
        $table->addIndex(['access_token'], ['name' => "access_token", 'unique' => false])->save();
        $table->addIndex(['deactivated'], ['name' => "deactivated", 'unique' => false])->save();
        $table->addIndex(['user_id','access_token'], ['name' => "user_token", 'unique' => true])->save();
        $table->addIndex(['soft_banned'], ['name' => "soft_banned", 'unique' => false])->save();
        $table->addIndex(['hard_banned'], ['name' => "hard_banned", 'unique' => false])->save();
        $table->addIndex(['user_id'], ['name' => "user_id", 'unique' => false])->save();
    }
}
