<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddWordsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("words", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('tag', 'string', ['null' => true, 'limit' => 10, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('length', 'integer', ['null' => true, 'limit' => MysqlAdapter::INT_REGULAR, 'precision' => 10, 'after' => 'tag']);
        $table->addColumn('words', 'text', ['null' => true, 'limit' => MysqlAdapter::TEXT_REGULAR, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'length']);
        $table->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'words']);
        $table->addColumn('updated_at', 'datetime', ['null' => true, 'after' => 'created_at']);
        $table->save();
        $table->addIndex(['tag'], ['name' => "tag", 'unique' => false])->save();
    }
}
