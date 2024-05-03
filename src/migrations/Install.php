<?php
/**
 */

namespace aodihis\productreview\migrations;

use aodihis\productreview\db\Table;
use aodihis\productreview\records\Review;
use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\commerce\elements\Order;
use craft\db\Migration;
use craft\db\Table as DbTable;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;
use ReflectionClass;
use yii\base\NotSupportedException;

/**
 * Installation Migration
 *
 * @author aodihis
 * @since 5.0
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();
        $this->dropProjectConfig();

        return true;
    }

    /**
     * Creates the tables for Craft Commerce
     */
    public function createTables(): void
    {
        
        $this->archiveTableIfExists(Table::PRODUCT_REVIEW_REVIEWS);
        $this->createTable(Table::PRODUCT_REVIEW_REVIEWS, [
            'id' => $this->primaryKey(),
            'productId' => $this->integer()->notNull(),
            'orderId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'updateCount' => $this->integer()->notNull()->defaultValue(0),
            'rating' => $this->tinyInteger(2),
            'comment' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists(Table::PRODUCT_REVIEW_VARIANTS);
        $this->createTable(Table::PRODUCT_REVIEW_VARIANTS, [
            'id' => $this->primaryKey(),
            'reviewId' => $this->integer()->notNull(),
            'variantId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Drop the tables
     */
    public function dropTables(): void
    {
        $tables = $this->_getAllTableNames();
        foreach ($tables as $table) {
            $this->dropTableIfExists($table);
        }
    }

    /**
     * Deletes the project config entry.
     */
    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove('product-review');
    }

    /**
     * Creates the indexes.
     */
    public function createIndexes(): void
    {
        $this->createIndex(null, Table::PRODUCT_REVIEW_REVIEWS, 'productId', false);
        $this->createIndex(null, Table::PRODUCT_REVIEW_REVIEWS, 'userId', false);
        $this->createIndex(null, Table::PRODUCT_REVIEW_REVIEWS, 'orderId', false);
        $this->createIndex(null, Table::PRODUCT_REVIEW_VARIANTS, 'variantId', false);
    }

    /**
     * Adds the foreign keys.
     */
    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, Table::PRODUCT_REVIEW_REVIEWS, ['productId'], CommerceTable::PRODUCTS, ['id'], 'CASCADE'); 
        $this->addForeignKey(null, Table::PRODUCT_REVIEW_REVIEWS, ['orderId'], CommerceTable::ORDERS, ['id'], 'CASCADE'); 
        $this->addForeignKey(null, Table::PRODUCT_REVIEW_REVIEWS, ['userId'], DbTable::USERS, ['id'], 'CASCADE');
        $this->addForeignKey(null, Table::PRODUCT_REVIEW_VARIANTS, ['reviewId'], Table::PRODUCT_REVIEW_REVIEWS, ['id'], 'CASCADE');
    }

    /**
     * Removes the foreign keys.
     */
    public function dropForeignKeys(): void
    {
        $tables = $this->_getAllTableNames();

        foreach ($tables as $table) {
            $this->_dropForeignKeyToAndFromTable($table);
        }
    }


    /**
     * Returns if the table exists.
     *
     * @param string $tableName
     * @return bool If the table exists.
     * @throws NotSupportedException
     */
    private function _tableExists(string $tableName): bool
    {
        $schema = $this->db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);

        return (bool)$table;
    }

    /**
     * @param $tableName
     * @throws NotSupportedException
     */
    private function _dropForeignKeyToAndFromTable($tableName): void
    {
        if ($this->_tableExists($tableName)) {
            $this->dropAllForeignKeysToTable($tableName);
            Db::dropAllForeignKeysToTable($tableName);
        }
    }

    /**
     * @return string[]
     */
    private function _getAllTableNames(): array
    {
        $class = new ReflectionClass(Table::class);
        return $class->getConstants();
    }
}
