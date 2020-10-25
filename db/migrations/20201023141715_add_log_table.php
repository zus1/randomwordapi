<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddLogTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("log", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('type', 'string', ['null' => true, 'limit' => 45, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('message', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'type']);
        $table->addColumn('code', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 5, 'after' => 'message']);
        $table->addColumn('file', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'code']);
        $table->addColumn('line', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 25, 'after' => 'file']);
        $table->addColumn('created_at', 'datetime', ['null' => false, 'default' => "CURRENT_TIMESTAMP", 'after' => 'line']);
        $table->addColumn('trace', 'text', ['null' => true, 'limit' => MysqlAdapter::TEXT_REGULAR, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'created_at']);
        $table->save();
        $table->addIndex(['message'], ['name' => "message", 'unique' => false])->save();
        $table->addIndex(['type'], ['name' => "type", 'unique' => false])->save();
    }
}
