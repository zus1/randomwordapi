<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddLocalTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("local", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('tag', 'string', ['null' => true, 'limit' => 45, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('active', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'tag']);
        $table->save();
        $table->addIndex(['tag'], ['name' => "tag", 'unique' => false])->save();
        $table->addIndex(['active'], ['name' => "active", 'unique' => false])->save();
        $table->addIndex(['tag','active'], ['name' => "tag_active", 'unique' => false])->save();
    }
}
