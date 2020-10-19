<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class AddCookieDisclaimerTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table("cookie_disclaimer", ['id' => false, 'primary_key' => ["id"], 'engine' => "InnoDB", 'encoding' => "utf8", 'collation' => "utf8_general_ci", 'comment' => "", 'row_format' => "Dynamic"]);
        $table->addColumn('id', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_BIG, 'precision' => 20, 'identity' => 'enable']);
        $table->addColumn('ip', 'string', ['null' => false, 'limit' => 225, 'collation' => "utf8_general_ci", 'encoding' => "utf8", 'after' => 'id']);
        $table->addColumn('accepted_at', 'datetime', ['null' => false, 'after' => 'ip']);
        $table->addColumn('accepted', 'integer', ['null' => false, 'limit' => MysqlAdapter::INT_TINY, 'precision' => 1, 'after' => 'accepted_at']);
        $table->save();
    }
}
