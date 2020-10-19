<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddPagesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("pages", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('name', 'string', ['null' => true, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('placeholders', 'string', ['null' => true, 'limit' => 1000, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'name']);
        $table->save();
        $table->addIndex(['name'], ['name' => "name", 'unique' => false])->save();
    }
}
