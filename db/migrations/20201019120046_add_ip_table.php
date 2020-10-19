<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddIpTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("ip", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('ip_address', 'string', ['null' => false, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('total_requests', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'after' => 'ip_address']);
        $table->addColumn('requests_period', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 11, 'after' => 'total_requests']);
        $table->addColumn('period_start', 'datetime', ['null' => true, 'after' => 'requests_period']);
        $table->addColumn('period_end', 'datetime', ['null' => true, 'after' => 'period_start']);
        $table->addColumn('period_limit', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'period_end']);
        $table->addColumn('hard_banned', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'period_limit']);
        $table->addColumn('soft_banned', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'hard_banned']);
        $table->addColumn('soft_banned_at', 'datetime', ['null' => true, 'after' => 'soft_banned']);
        $table->addColumn('soft_ban_count', 'integer', ['null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 5, 'after' => 'soft_banned_at']);
        $table->addColumn('hard_banned_at', 'datetime', ['null' => true, 'after' => 'soft_ban_count']);
        $table->save();
        $table->addIndex(['ip_address'], ['name' => "ip_address", 'unique' => false])->save();
        $table->addIndex(['soft_banned'], ['name' => "soft_banned", 'unique' => false])->save();
        $table->addIndex(['ip_address','total_requests'], ['name' => "ip_requests", 'unique' => false])->save();
        $table->addIndex(['hard_banned'], ['name' => "hard_banned", 'unique' => false])->save();
        $table->addIndex(['total_requests'], ['name' => "total_requests", 'unique' => false])->save();
    }
}
