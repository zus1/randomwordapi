<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddPageContentTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("page_content", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('page_name', 'string', ['null' => true, 'limit' => 45, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('local', 'string', ['null' => true, 'limit' => 45, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'page_name']);
        $table->addColumn('placeholder', 'string', ['null' => true, 'limit' => 100, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'local']);
        $table->addColumn('content', 'text', ['null' => true, 'limit' => MysqlAdapter::TEXT_REGULAR, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'placeholder']);
        $table->save();
        $table->addIndex(['page_name'], ['name' => "page_name", 'unique' => false])->save();
        $table->addIndex(['local'], ['name' => "local", 'unique' => false])->save();
        $table->addIndex(['page_name','local'], ['name' => "local_page_name", 'unique' => false])->save();
    }
}
