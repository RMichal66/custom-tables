<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC')) {
	die('Restricted access');
}

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;

class MySQLWhereClause
{
	public array $conditions = [];
	public array $orConditions = [];
	public array $nestedConditions = [];
	public array $nestedOrConditions = [];

	public function hasConditions(): bool
	{
		if (count($this->conditions) > 0)
			return true;

		if (count($this->orConditions) > 0)
			return true;

		if (count($this->nestedConditions) > 0)
			return true;

		if (count($this->nestedOrConditions) > 0)
			return true;

		return false;
	}

	public function addConditionsFromArray(array $conditions): void
	{
		foreach ($conditions as $fieldName => $fieldValue) {
			// Assuming default operator is '=' if not specified
			$this->addCondition($fieldName, $fieldValue);
		}
	}

	public function addCondition($fieldName, $fieldValue, $operator = '=', $sanitized = false): void
	{
		$operator = strtoupper($operator);

		$possibleOperators = ['=', '>', '<', '!=', '>=', '<=', 'LIKE', 'NULL', 'NOT NULL', 'INSTR', 'NOT INSTR', 'IN'];

		if (!in_array($operator, $possibleOperators)) {
			throw new \mysql_xdevapi\Exception('SQL Where Clause operator "' . common::ctStripTags($operator) . '" not recognized.');
		}

		$this->conditions[] = [
			'field' => $fieldName,
			'value' => $fieldValue,
			'operator' => $operator,
			'sanitized' => $sanitized
		];
	}

	public function addOrCondition($fieldName, $fieldValue, $operator = '=', $sanitized = false): void
	{
		$operator = strtoupper($operator);

		$possibleOperators = ['=', '>', '<', '!=', '>=', '<=', 'LIKE', 'NULL', 'NOT NULL', 'INSTR', 'NOT INSTR', 'IN'];

		if (!in_array($operator, $possibleOperators)) {
			throw new \mysql_xdevapi\Exception('SQL Where Clause operator "' . common::ctStripTags($operator) . '" not recognized.');
		}

		$this->orConditions[] = [
			'field' => $fieldName,
			'value' => $fieldValue,
			'operator' => $operator,
			'sanitized' => $sanitized
		];

	}

	public function addNestedCondition(MySQLWhereClause $condition): void
	{
		$this->nestedConditions[] = $condition;
	}

	public function addNestedOrCondition(MySQLWhereClause $orCondition): void
	{
		$this->nestedOrConditions[] = $orCondition;
	}

	public function __toString(): string
	{
		return $this->getWhereClause();// Returns the "where" clause with %d,%f,%s placeholders
	}

	public function getWhereClause(string $logicalOperator = 'AND'): string
	{
		$where = [];

		// Process regular conditions
		if (count($this->conditions))
			$where [] = self::getWhereClauseMergeConditions($this->conditions);

		// Process OR conditions
		if (count($this->orConditions) > 0) {
			if (count($this->orConditions) === 1)
				$where [] = self::getWhereClauseMergeConditions($this->orConditions, 'OR');
			else
				$where [] = '(' . self::getWhereClauseMergeConditions($this->orConditions, 'OR') . ')';
		}
		// Process nested conditions
		foreach ($this->nestedConditions as $nestedCondition)
			$where [] = $nestedCondition->getWhereClause();

		$orWhere = [];
		foreach ($this->nestedOrConditions as $nestedOrCondition) {
			if ($nestedOrCondition->countConditions() == 1)
				$orWhere [] = $nestedOrCondition->getWhereClause('OR');
			else
				$orWhere [] = '(' . $nestedOrCondition->getWhereClause('OR') . ')';
		}

		if (count($orWhere) > 0)
			$where [] = implode(' OR ', $orWhere);

		return implode(' ' . $logicalOperator . ' ', $where);
	}

	protected function getWhereClauseMergeConditions($conditions, $logicalOperator = 'AND'): string
	{
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		if ($version < 4)
			$db = Factory::getDbo();
		else
			$db = Factory::getContainer()->get('DatabaseDriver');

		$where = [];

		foreach ($conditions as $condition) {

			if ($condition['value'] === null) {
				$where [] = $condition['field'];
			} elseif ($condition['operator'] == 'NULL') {
				$where [] = $condition['field'] . ' IS NULL';
			} elseif ($condition['operator'] == 'NOT NULL') {
				$where [] = $condition['field'] . ' IS NOT NULL';
			} elseif ($condition['operator'] == 'LIKE') {
				$where [] = $condition['field'] . ' LIKE ' . $db->quote($condition['value']);
			} elseif ($condition['operator'] == 'INSTR') {
				if ($condition['sanitized']) {
					$where [] = 'INSTR(' . $condition['field'] . ',' . $condition['value'] . ')';
				} else {
					$where [] = 'INSTR(' . $condition['field'] . ',' . $db->quote($condition['value']) . ')';
				}
			} elseif ($condition['operator'] == 'NOT INSTR') {
				if ($condition['sanitized']) {
					$where [] = '!INSTR(' . $condition['field'] . ',' . $condition['value'] . ')';
				} else {
					$where [] = '!INSTR(' . $condition['field'] . ',' . $db->quote($condition['value']) . ')';
				}
			} elseif ($condition['operator'] == 'REGEXP') {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' REGEXP ' . $condition['value'];
				} else {
					$where [] = $condition['field'] . ' REGEXP ' . $db->quote($condition['value']);
				}
			} elseif ($condition['operator'] == 'IN') {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' IN ' . $condition['value'];
				} else {
					$where [] = $db->quote($condition['field']) . ' IN ' . $condition['value'];
				}
			} else {
				if ($condition['sanitized']) {
					$where [] = $condition['field'] . ' ' . $condition['operator'] . ' ' . $condition['value'];
				} else {

					$where_string = $condition['field'] . ' ' . $condition['operator'] . ' ';

					if (is_bool($condition['value']))
						$where_string .= $condition['value'] ? 'TRUE' : 'FALSE';
					elseif (is_int($condition['value']))
						$where_string .= $condition['value'];
					elseif (is_float($condition['value']))
						$where_string .= $condition['value'];
					else
						$where_string .= $db->quote($condition['value']);

					$where [] = $where_string;
				}
			}
		}
		return implode(' ' . $logicalOperator . ' ', $where);
	}

	public function countConditions(): int
	{
		return count($this->conditions) + count($this->orConditions) + count($this->nestedConditions) + count($this->nestedOrConditions);
	}
}

class database
{
	public static function realTableName($tableName): ?string
	{
		$db = self::getDB();
		return str_replace('#__', $db->getPrefix(), $tableName);
	}

	public static function getDB(): DatabaseInterface
	{
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();

		if ($version < 4)
			return Factory::getDbo();
		else
			//return Factory::getContainer()->get('DatabaseDriver');
			return Factory::getContainer()->get(DatabaseInterface::class);

	}

	/**
	 * Inserts data into a database table in a cross-platform manner (Joomla and WordPress).
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array of data to insert. Keys represent column names, values represent values to be inserted.
	 *
	 * @return int|null The ID of the last inserted record, or null if the insert operation failed.
	 * @throws Exception If an error occurs during the insert operation.
	 *
	 * @since 3.1.8
	 */
	public static function insert(string $tableName, array $data): ?int
	{
		$db = self::getDB();

		$query = $db->getQuery(true);

		// Construct the insert statement
		$columns = array();
		$values = array();

		foreach ($data as $key => $value) {
			$columns[] = $db->quoteName($key);

			if (is_array($value) and count($value) == 2 and $value[1] == 'sanitized') {
				$values[] = $value[0];
			} else {
				if ($value === null)
					$values[] = 'NULL';
				elseif (is_bool($value))
					$values[] = $value ? 'TRUE' : 'FALSE';
				elseif (is_int($value))
					$values[] = $value;
				elseif (is_float($value))
					$values[] = $value;
				else
					$values[] = $db->quote($value);
			}
		}

		$query->insert($db->quoteName($tableName))
			->columns($columns)
			->values(implode(',', $values));

		$db->setQuery($query);

		try {
			$db->execute();
			return $db->insertid(); // Return the last inserted ID
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	public static function quote($value): ?string
	{
		$db = self::getDB();
		return $db->quote($value);
	}

	/**
	 * @throws Exception
	 * @since 3.2.2
	 */
	public static function getVersion(): ?float
	{
		$db = self::getDB();
		$result = $db->loadAssocList('select @@version');
		return floatval($result[0]['@@version']);
	}

	/**
	 * @throws Exception
	 * @since 3.1.8
	 */
	public static function loadAssocList(string  $table, array $selects, MySQLWhereClause $whereClause,
	                                     ?string $order = null, ?string $orderBy = null,
	                                     ?int    $limit = null, ?int $limitStart = null,
	                                     string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'ARRAY_A', $groupBy, $returnQueryString);
	}

	public static function loadObjectList(string  $table, array $selectsRaw, MySQLWhereClause $whereClause,
	                                      ?string $order = null, ?string $orderBy = null,
	                                      ?int    $limit = null, ?int $limitStart = null,
	                                      string  $output_type = 'OBJECT', string $groupBy = null,
	                                      bool    $returnQueryString = false)
	{
		$db = self::getDB();

		$selects = $selectsRaw;

		$query = $db->getQuery(true);

		$query->select(implode(',', $selects));
		$query->from($table);

		if ($whereClause->hasConditions())
			$query->where($whereClause->getWhereClause());

		if (!empty($groupBy))
			$query->group($groupBy);

		if (!empty($order))
			$query->order($order . ($orderBy !== null and strtolower($orderBy) == 'desc' ? ' DESC' : ''));

		if ($returnQueryString)
			return $query;

		if ($limitStart !== null and $limit !== null)
			$query->setLimit($limit, $limitStart);
		elseif ($limitStart !== null and $limit === null)
			$query->setLimit(20000, $limitStart);
		if ($limitStart === null and $limit !== null)
			$query->setLimit($limit);

		$db->setQuery($query);

		if ($output_type == 'OBJECT')
			return $db->loadObjectList();
		else if ($output_type == 'ROW_LIST')
			return $db->loadRowList();
		else if ($output_type == 'COLUMN')
			return $db->loadColumn();
		else
			return $db->loadAssocList();
	}

	public static function loadRowList(string  $table, array $selects, MySQLWhereClause $whereClause,
	                                   ?string $order = null, ?string $orderBy = null,
	                                   ?int    $limit = null, ?int $limitStart = null,
	                                   string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'ROW_LIST', $groupBy, $returnQueryString);
	}

	public static function loadColumn(string  $table, array $selects, MySQLWhereClause $whereClause,
	                                  ?string $order = null, ?string $orderBy = null,
	                                  ?int    $limit = null, ?int $limitStart = null,
	                                  string  $groupBy = null, bool $returnQueryString = false)
	{
		return self::loadObjectList($table, $selects, $whereClause, $order, $orderBy, $limit, $limitStart, 'COLUMN', $groupBy, $returnQueryString);
	}

	public static function getTableStatus(string $tablename, string $type = 'table')
	{
		$conf = Factory::getConfig();
		$database = $conf->get('db');
		$dbPrefix = $conf->get('dbprefix');

		$db = self::getDB();

		if ($type == 'gallery')
			$realTableName = $dbPrefix . 'customtables_gallery_' . $tablename;
		elseif ($type == 'filebox')
			$realTableName = $dbPrefix . 'customtables_filebox_' . $tablename;
		elseif ($type == 'native')
			$realTableName = $tablename;
		else
			$realTableName = $dbPrefix . 'customtables_' . $tablename;

		$db->setQuery('SHOW TABLE STATUS FROM ' . $db->quoteName($database) . ' LIKE ' . $db->quote($realTableName));
		return $db->loadObjectList();
	}

	public static function getTableIndex(string $tableName, string $fieldName)
	{
		$db = self::getDB();

		$db->setQuery('SHOW INDEX FROM ' . $tableName . ' WHERE Key_name = "' . $fieldName . '"');
		return $db->loadObjectList();
	}

	public static function showCreateTable($tableName): array
	{
		$db = self::getDB();

		$db->setQuery("SHOW CREATE TABLE " . $tableName);
		return $db->loadAssocList();
	}

	/**
	 * Updates data in a database table in a cross-platform manner (Joomla and WordPress).
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array $data An associative array of data to update. Keys represent column names, values represent new values.
	 * @param array $where An associative array specifying which rows to update. Keys represent column names, values represent conditions for the update.
	 *
	 * @return bool True if the update operation is successful, otherwise false.
	 * @throws Exception If an error occurs during the update operation.
	 *
	 * @since 3.1.8
	 */
	public static function update(string $tableName, array $data, MySQLWhereClause $whereClause): bool
	{
		if (!$whereClause->hasConditions()) {
			throw new Exception('Update database table records without WHERE clause is prohibited.');
		}

		if (count($data) == 0)
			return true;

		$db = self::getDB();

		$fields = self::prepareFields($db, $data);

		$query = $db->getQuery(true);

		$query->update($db->quoteName($tableName))
			->set($fields)
			->where($whereClause->getWhereClause());

		$db->setQuery($query);

		try {
			$db->execute();
			return true; // Update successful
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	protected static function prepareFields($db, $data): array
	{
		// Construct the update statement
		$fields = array();
		foreach ($data as $key => $value) {
			if (is_array($value) and count($value) == 2 and $value[1] == 'sanitized') {
				$fields[] = $db->quoteName($key) . '=' . $value[0];
			} else {
				if ($value === null)
					$valueCleaned = 'NULL';
				elseif (is_bool($value))
					$valueCleaned = $value ? 'TRUE' : 'FALSE';
				elseif (is_int($value))
					$valueCleaned = $value;
				elseif (is_float($value))
					$valueCleaned = $value;
				else
					$valueCleaned = $db->quote($value);

				$fields[] = $db->quoteName($key) . '=' . $valueCleaned;
			}
		}
		return $fields;
	}

	public static function deleteRecord(string $tableName, string $realIdFieldName, $id): void
	{
		$db = self::getDB();

		$query = $db->getQuery(true);
		$query->delete($tableName);

		if (is_int($id))
			$query->where($db->quoteName($realIdFieldName) . '=' . $id);
		else
			$query->where($db->quoteName($realIdFieldName) . '=' . $db->quote($id));

		$db->setQuery($query);
		$db->execute();
	}

	public static function deleteTableLessFields(): void
	{
		$db = self::getDB();
		$db->setQuery('DELETE FROM #__customtables_fields AS f WHERE (SELECT id FROM #__customtables_tables AS t WHERE t.id = f.tableid) IS NULL');
		$db->execute();
	}

	public static function dropTableIfExists(string $tablename, string $type = 'table'): void
	{
		$db = self::getDB();

		if ($type == 'gallery')
			$realTableName = '#__customtables_gallery_' . $tablename;
		elseif ($type == 'filebox')
			$realTableName = '#__customtables_filebox_' . $tablename;
		else
			$realTableName = '#__customtables_' . $tablename;

		$serverType = self::getServerType();

		if ($serverType == 'postgresql') {

			$db->setQuery('DROP TABLE IF EXISTS ' . $realTableName);
			$db->execute();

			$db->setQuery('DROP SEQUENCE IF EXISTS ' . $realTableName . '_seq CASCADE');
			$db->execute();

		} else {
			$query = 'DROP TABLE IF EXISTS ' . $db->quoteName($realTableName);
			$db->setQuery($query);
			$db->execute();
		}
	}

	public static function getServerType(): ?string
	{
		$db = self::getDB();
		return $db->serverType;
	}

	public static function dropColumn(string $realTableName, string $columnName): void
	{
		$db = self::getDB();

		$db->setQuery('SET foreign_key_checks = 0');
		$db->execute();

		$db->setQuery('ALTER TABLE `' . $realTableName . '` DROP COLUMN `' . $columnName . '`');
		$db->execute();

		$db->setQuery('SET foreign_key_checks = 1');
		$db->execute();
	}

	public static function addForeignKey(string $realTableName, string $columnName, string $join_with_table_name, string $join_with_table_field): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ADD FOREIGN KEY (' . $columnName . ') REFERENCES '
			. $db->quoteName(self::getDataBaseName() . '.' . $join_with_table_name) . ' (' . $join_with_table_field . ') ON DELETE RESTRICT ON UPDATE RESTRICT');
		$db->execute();
	}

	public static function getDataBaseName(): ?string
	{
		$conf = Factory::getConfig();
		return $conf->get('db');
	}

	public static function dropForeignKey(string $realTableName, string $constrance): void
	{
		$db = self::getDB();

		$db->setQuery('SET foreign_key_checks = 0');
		$db->execute();

		$db->setQuery('ALTER TABLE ' . $realTableName . ' DROP FOREIGN KEY ' . $constrance);
		$db->execute();

		$db->setQuery('SET foreign_key_checks = 1');
		$db->execute();
	}

	public static function setTableInnoDBEngine(string $realTableName, string $comment): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $realTableName . ' ENGINE = InnoDB');
		$db->execute();
	}

	public static function changeTableComment(string $realTableName, string $comment): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $realTableName . ' COMMENT ' . $db->quote($comment));
		$db->execute();
	}

	public static function addIndex(string $realTableName, string $columnName): void
	{
		$db = self::getDB();
		$db->setQuery('ALTER TABLE ' . $realTableName . ' ADD INDEX (' . $columnName . ')');
		$db->execute();
	}

	public static function addColumn(string $realTableName, string $columnName, string $type, ?bool $nullable, ?string $extra = null, ?string $comment = null): void
	{
		$db = self::getDB();

		$db->setQuery('ALTER TABLE ' . $realTableName . ' ADD COLUMN ' . $columnName . ' ' . $type
			. ($nullable !== null ? ($nullable ? ' NULL' : ' NOT NULL') : '')
			. ($extra !== null ? ' ' . $extra : '')
			. ($comment !== null ? ' COMMENT ' . database::quote($comment) : ''));
		$db->execute();
	}

	public static function createTable(string $realTableName, string $privateKey, array $columns, string $comment, ?array $keys = null, string $primaryKeyType = 'int'): void
	{
		$db = self::getDB();

		if (self::getServerType() == 'postgresql') {

			$db->setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $realTableName . '_seq');
			$db->execute();

			$allColumns = array_merge([$privateKey . ' ' . $primaryKeyType . ' NOT NULL DEFAULT nextval (\'' . $realTableName . '_seq\')'], $columns);

			$query = 'CREATE TABLE IF NOT EXISTS ' . $realTableName . '(' . implode(',', $allColumns) . ')';
			$db->setQuery($query);
			$db->execute();

			$db->setQuery('ALTER SEQUENCE ' . $realTableName . '_seq RESTART WITH 1');
			$db->execute();

		} else {

			$primaryKeyTypeString = 'INT UNSIGNED';//(11)
			if ($primaryKeyType !== 'int')
				$primaryKeyTypeString = $primaryKeyType;

			$allColumns = array_merge(['`' . $privateKey . '` ' . $primaryKeyTypeString . ' NOT NULL AUTO_INCREMENT'], $columns, ['PRIMARY KEY  (`id`)']);

			if ($keys !== null)
				$allColumns = array_merge($allColumns, $keys);

			$query = 'CREATE TABLE IF NOT EXISTS ' . $realTableName
				. '(' . implode(',', $allColumns) . ') ENGINE=InnoDB COMMENT="' . $comment . '"'
				. ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AUTO_INCREMENT=1;';

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * @throws Exception
	 * @since 3.2.5
	 */
	public static function copyCTTable(string $newTableName, string $oldTableName): void
	{
		$db = self::getDB();

		$realNewTableName = '#__customtables_table_' . $newTableName;

		if (self::getServerType() == 'postgresql') {

			$db->setQuery('CREATE SEQUENCE IF NOT EXISTS ' . $realNewTableName . '_seq');
			$db->execute();

			$db->setQuery('CREATE TABLE ' . $realNewTableName . ' AS TABLE #__customtables_table_' . $oldTableName);
			$db->execute();

			$db->setQuery('ALTER SEQUENCE ' . $realNewTableName . '_seq RESTART WITH 1');
			$db->execute();
		} else {
			$db->setQuery('CREATE TABLE ' . $realNewTableName . ' AS SELECT * FROM #__customtables_table_' . $oldTableName);
			$db->execute();
		}

		$db->setQuery('ALTER TABLE ' . $realNewTableName . ' ADD PRIMARY KEY (id)');
		$db->execute();

		$PureFieldType = [
			'data_type' => 'int',
			'is_unsigned' => true,
			'is_nullable' => false,
			'autoincrement' => true
		];
		database::changeColumn($realNewTableName, 'id', 'id', $PureFieldType, 'Primary Key');
	}

	/**
	 * @throws Exception
	 * @since 3.2.5
	 */
	public static function changeColumn(string $realTableName, string $oldColumnName, string $newColumnName, array $PureFieldType, ?string $comment = null): void
	{
		if (!str_contains($realTableName, 'customtables_'))
			throw new Exception('Only CustomTables tables can be modified.');

		$possibleTypes = ['varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'blob', 'mediumblob',
			'longblob', 'char', 'int', 'bigint', 'numeric', 'decimal', 'smallint', 'tinyint', 'date', 'TIMESTAMP', 'datetime'];

		if (!in_array($PureFieldType['data_type'], $possibleTypes))
			throw new Exception('Change Column type: unsupported column type "' . $PureFieldType['data_type'] . '"');

		$db = self::getDB();

		if (self::getServerType() == 'postgresql') {

			if ($oldColumnName != $newColumnName)
				$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' RENAME COLUMN ' . $db->quoteName($oldColumnName) . ' TO ' . $db->quoteName($newColumnName));

			$db->execute();

			$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' ALTER COLUMN'
				. ' ' . $db->quoteName($newColumnName)
				. ' TYPE ' . $PureFieldType['data_type']
			);
			$db->execute();
		} else {

			$type = $PureFieldType['data_type'];
			if (($PureFieldType['length'] ?? '') != '') {
				if (str_contains($PureFieldType['length'], ',')) {
					$parts = explode(',', $PureFieldType['length']);
					$partsInt = [];
					foreach ($parts as $part)
						$partsInt[] = (int)$part;

					$type .= '(' . implode(',', $partsInt) . ')';
				} else
					$type .= '(' . (int)$PureFieldType['length'] . ')';
			}

			if ($PureFieldType['is_unsigned'] ?? false)
				$type .= ' UNSIGNED';

			$db->setQuery('ALTER TABLE ' . $db->quoteName($realTableName) . ' CHANGE ' . $db->quoteName($oldColumnName) . ' ' . $db->quoteName($newColumnName)
				. ' ' . $type
				. (($PureFieldType['is_nullable'] ?? false) ? ' NULL' : ' NOT NULL')
				. (($PureFieldType['default'] ?? '') != "" ? ' DEFAULT ' . (is_numeric($PureFieldType['default']) ? $PureFieldType['default'] : $db->quote($PureFieldType['default'])) : '')
				. (($PureFieldType['autoincrement'] ?? false) ? ' AUTO_INCREMENT' : '')
				. ' COMMENT ' . $db->quote($comment));
			$db->execute();
		}
	}

	public static function showTables()
	{
		$db = self::getDB();
		return $db->loadAssocList('SHOW TABLES');
	}

	public static function renameTable(string $oldCTTableName, string $newCTTableName, string $type = 'table'): void
	{
		$db = self::getDB();
		$database = self::getDataBaseName();

		if ($type == 'gallery') {
			$oldTableName = '#__customtables_gallery_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_gallery_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		} elseif ($type == 'filebox') {
			$oldTableName = '#__customtables_filebox_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_filebox_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		} else {
			$oldTableName = '#__customtables_table_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $oldCTTableName)));
			$newTableName = '#__customtables_table_' . strtolower(trim(preg_replace("/[^a-zA-Z_\d]/", "", $newCTTableName)));
		}

		$db->setQuery('RENAME TABLE ' . $db->quoteName($database . '.' . $oldTableName) . ' TO '
			. $db->quoteName($database . '.' . $newTableName));
		$db->execute();
	}

	public static function getDBPrefix(): ?string
	{
		$conf = Factory::getConfig();
		return $conf->get('dbprefix');
	}
}