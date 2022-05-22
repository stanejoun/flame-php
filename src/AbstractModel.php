<?php

namespace Stanejoun\LightPHP;

abstract class AbstractModel extends AbstractCommon
{
	public int $id = 0;

	public static function findBy(string $field, mixed $value): array
	{
		return self::find((new Query())
			->where("$field = :value")
			->args([':value' => $value])
		);
	}

	/**
	 * @param Query|null $query
	 * @param bool|string[] $hydrate
	 * @param bool $returnAbstractModel
	 * @return array
	 */
	public static function find(?Query $query = null, mixed $hydrate = true, bool $returnAbstractModel = true): array
	{
		$object = self::getObject();
		$classNamesToBeHydrated = is_array($hydrate) ? $hydrate : [];
		if (!empty($classNamesToBeHydrated)) {
			array_unshift($classNamesToBeHydrated, static::class);
			$select = [];
			/** @var AbstractModel $className */
			foreach ($classNamesToBeHydrated as $className) {
				$currentObject = new $className();
				$currentTableName = $currentObject->modelDescription()->getTable();
				$currentProperties = $currentObject->modelDescription()->getProperties();
				foreach ($currentProperties as $property) {
					$select[] = "`$currentTableName`.$property as {$currentTableName}__$property";
				}
			}
			if ('*' === $query->getSelect()) {
				$query->select(implode(', ', $select));
			}
		} else {
			$object = self::getObject();
		}
		$sql = self::getSqlFromClauses($object, $query);
		$data = (!empty($query) && $query->getArgs()) ? $query->getArgs() : [];
		$results = $object->select($sql, $data);
		if ($hydrate === false) {
			if (!ModelDescription::$DISABLE_AUTO_ENCRYPTION && $object->modelDescription()->hasEncryptedFields()) {
				foreach ($results as $index => $result) {
					foreach ($result as $key => $value) {
						if (in_array($key, $object->modelDescription()->getEncryptedFields())) {
							$results[$index][$key] = Security::decrypt($value);
						}
					}
				}
			}
			return $results;
		}
		$collection = [];
		foreach ($results as $result) {
			if (!empty($classNamesToBeHydrated)) {
				$objects = [];
				foreach ($classNamesToBeHydrated as $className) {
					$currentDefinition = [];
					/** @var AbstractModel $currentObject */
					$currentObject = new $className();
					$currentTableName = $currentObject->modelDescription()->getTable();
					foreach ($result as $currentColumnName => $value) {
						if (str_contains($currentColumnName, "{$currentTableName}__")) {
							$columnName = str_replace("{$currentTableName}__", '', $currentColumnName);
							$currentDefinition[$columnName] = $value;
						}
					}
					$currentObject->hydrate($currentDefinition);
					$objects[] = $currentObject;
				}
				$object = null;
				$propertiesNames = [];
				foreach ($classNamesToBeHydrated as $index => $className) {
					$currentObject = $objects[$index];
					if ($index === 0) {
						$object = $currentObject;
					} else {
						$propertyName = $currentObject->modelDescription()->getTable();
						if (in_array("{$propertyName}_id", $object->modelDescription()->getProperties())) {
							if (!property_exists($object, $propertyName)) {
								$object->{$propertyName} = null;
							}
							if ($currentObject->getId() > 0) {
								Helper::setPropertyValue($object, $propertyName, $currentObject);
							}
						} else {
							$propertyName .= 's';
							$propertiesNames[$propertyName] = $propertyName;
							if (!property_exists($object, $propertyName)) {
								$object->{$propertyName} = [];
							}
							if ($currentObject->getId() > 0) {
								Helper::addPropertyValue($object, $propertyName, $currentObject);
							}
						}
					}
				}
				if (isset($collection[$object->getId()])) {
					foreach ($propertiesNames as $propertyName) {
						$collection[$object->getId()]->{$propertyName} = array_merge($collection[$object->getId()]->{$propertyName}, $object->{$propertyName});
					}
				} else {
					$collection[$object->getId()] = $object;
				}
			} else {
				$object = self::getObject();
				$object->hydrate($result);
				$collection[] = $object;
			}
		}
		if (is_array($hydrate) && !$returnAbstractModel) {
			$resultAsStdClassCollection = [];
			foreach ($collection as $currentItem) {
				$resultAsStdClassCollection[] = $currentItem->toObject();
			}
			return $resultAsStdClassCollection;
		}
		return array_values($collection);
	}

	private static function getObject(): AbstractModel
	{
		$Class = static::class;
		return new $Class();
	}

	private static function getSqlFromClauses(AbstractModel $object, ?Query $query = null): string
	{
		$tableName = $object->modelDescription()->getTable();
		$select = "`$tableName`.*";
		$from = "`$tableName`";
		$join = '';
		$where = '';
		$groupBy = '';
		$having = '';
		$orderBy = '';
		$limit = '';
		if (!empty($query)) {
			if ($query->getSelect() && '*' !== $query->getSelect()) {
				$select = $query->getSelect();
			}
			if ($query->getFrom()) {
				$from = $query->getFrom();
			}
			if ($query->getJoin()) {
				$join = $query->getJoin();
			}
			if ($query->getWhere()) {
				$where = 'WHERE ' . $query->getWhere();
			}
			if ($query->getGroupBy()) {
				$groupBy = 'GROUP BY ' . $query->getGroupBy();
			}
			if ($query->getHaving()) {
				$having = 'HAVING ' . $query->getHaving();
			}
			if ($query->getOrderBy()) {
				$orderBy = 'ORDER BY ' . $query->getOrderBy();
			}
			if ($query->getLimit()) {
				$limit = 'LIMIT ' . $query->getLimit();
			}
		}
		if (!ModelDescription::$DISABLE_DELETED_AT && $object->modelDescription()->isSoftDelete() && !str_contains($where, 'deleted_at IS NULL')) {
			if (empty($where)) {
				$where = "WHERE `$tableName`.deleted_at IS NULL";
			} else {
				$where .= " AND `$tableName`.deleted_at IS NULL";
			}
		}
		$sql = "SELECT $select FROM $from $join $where $groupBy $having $orderBy $limit";
		$sql = trim($sql);
		return "$sql;";
	}

	public function modelDescription(): ModelDescription
	{
		$reflectionClass = new \ReflectionClass($this);
		$className = $reflectionClass->getName();
		if (!isset(ModelDescription::$DESCRIPTIONS[$className])) {
			$attributes = $reflectionClass->getAttributes();
			if (empty($attributes) || empty($attributes[0])) {
				throw new \RuntimeException('Unable to generate the model description!');
			}
			/** @var ModelDescription $modelDescription */
			$modelDescription = $attributes[0]->newInstance();
			$properties = $reflectionClass->getProperties();
			$staticProperties = $reflectionClass->getStaticProperties();
			$staticPropertiesNames = [];
			foreach ($staticProperties as $staticPropertyName => $staticPropertyValue) {
				$staticPropertiesNames[] = $staticPropertyName;
			}
			$objectProperties = [];
			foreach ($properties as $reflectionProperty) {
				$property = $reflectionProperty->name;
				if (!in_array($property, $staticPropertiesNames) && !in_array($property, $modelDescription->getUnmappedProperties(), true)) {
					$objectProperties[] = $property;
				}
			}
			$modelDescription->setProperties($objectProperties);
			ModelDescription::$DESCRIPTIONS[$className] = $modelDescription;
		}
		return ModelDescription::$DESCRIPTIONS[$className];
	}

	public function select(string $sql, array $data = []): array
	{
		if (!ModelDescription::$DISABLE_AUTO_ENCRYPTION && $this->modelDescription()->hasEncryptedFields()) {
			foreach ($this->modelDescription()->getEncryptedFields() as $encryptedField) {
				if (isset($data[$encryptedField]) && !is_null($data[$encryptedField])) {
					if (is_array($data[$encryptedField])) {
						foreach ($data[$encryptedField] as $key => $dataEncryptedField) {
							$data[$encryptedField][$key] = Security::encrypt($dataEncryptedField);
						}
					} else {
						$data[$encryptedField] = Security::encrypt($data[$encryptedField]);
					}
				}
				if (isset($data[":$encryptedField"]) && !is_null($data[":$encryptedField"])) {
					if (is_array($data[":$encryptedField"])) {
						foreach ($data[":$encryptedField"] as $key => $dataEncryptedField) {
							$data[":$encryptedField"][$key] = Security::encrypt($dataEncryptedField);
						}
					} else {
						$data[":$encryptedField"] = Security::encrypt($data[":$encryptedField"]);
					}
				}
			}
		}
		$stmt = DataBase::executeQuery($sql, $data);
		$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		return $results;
	}

	public function hydrate(mixed $definition): void
	{
		Helper::hydrate($this, $definition);
		if (!ModelDescription::$DISABLE_AUTO_ENCRYPTION && $this->modelDescription()->hasEncryptedFields()) {
			$this->decrypt();
		}
	}

	protected function decrypt(): self
	{
		$encryptedFields = $this->modelDescription()->getEncryptedFields();
		foreach ($encryptedFields as $encryptedField) {
			$propertyValue = Helper::getPropertyValue($this, $encryptedField);
			$decryptedValue = Security::decrypt($propertyValue);
			Helper::setPropertyValue($this, $encryptedField, $decryptedValue);
		}
		return $this;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): AbstractModel
	{
		$this->id = $id;
		return $this;
	}

	public static function findById(mixed $value): array
	{
		return self::find((new Query())
			->where('id = :value')
			->args([':value' => $value])
		);
	}

	public static function findOneBy(string $field, mixed $value): ?AbstractModel
	{
		return self::findOne((new Query())
			->where("$field = :value")
			->args([':value' => $value])
		);
	}

	/**
	 * @param Query|null $query
	 * @param bool|string[] $hydrate
	 * @return AbstractModel|null
	 */
	public static function findOne(?Query $query = null, mixed $hydrate = true): ?AbstractModel
	{
		if (is_array($hydrate)) {
			$results = self::find($query, $hydrate);
			return (!empty($results)) ? $results[0] : null;
		}
		$object = self::getObject();
		if (empty($query)) {
			$query = new Query();
			$query->limit('1');
		}
		$sql = self::getSqlFromClauses($object, $query);
		$data = ($query->getArgs()) ?: [];
		$results = $object->select($sql, $data);
		if (!empty($results[0])) {
			if ($hydrate) {
				$object->hydrate($results[0]);
				return $object;
			}
			return $results[0];
		}
		return null;
	}

	public static function findOneById(mixed $value): ?AbstractModel
	{
		return self::findOne((new Query())
			->where('id = :value')
			->args([':value' => $value])
		);
	}

	public static function deleteAll(?Query $query = null)
	{
		$objectsToRemoved = self::find($query);
		if (!empty($objectsToRemoved)) {
			foreach ($objectsToRemoved as $objectToRemoved) {
				$objectToRemoved->delete();
			}
		}
	}

	/**
	 * @param Query|null $query
	 * @param int $page
	 * @param int $limit
	 * @param bool|string[] $hydrate
	 * @return SearchResult
	 */
	public static function search(?Query $query = null, int $page = 1, int $limit = 10, mixed $hydrate = false): SearchResult
	{
		if (empty($query)) {
			$query = new Query();
		}
		$object = self::getObject();
		$originalSelectClause = $query->getSelect();
		$query->select('COUNT(`' . $object->modelDescription()->getTable() . '`.id) as nbTotal');
		$result = $object->select(self::getSqlFromClauses($object, $query), $query->getArgs());
		$start = ($page - 1) * $limit;
		if (!empty($result) && isset($result[0]['nbTotal'])) {
			$total = $result[0]['nbTotal'];
			$numberOfPages = ceil($total / $limit);
			if ($page > $numberOfPages) {
				$page = $numberOfPages;
			}
			$query->select($originalSelectClause);
			$query->limit("$start, $limit");
			$items = self::find($query, $hydrate);
		} else {
			$total = 0;
			$numberOfPages = 0;
			$items = [];
		}
		$searchResult = new SearchResult();
		$searchResult->total = $total;
		$searchResult->items = $items;
		$searchResult->start = $start;
		$searchResult->limit = $limit;
		$searchResult->numberOfPages = $numberOfPages;
		$searchResult->page = $page;
		return $searchResult;
	}

	public static function distinct($column, int $limit = 100, ?Query $query = null): array
	{
		if (empty($query)) {
			$query = new Query();
		}
		$object = self::getObject();
		$originalSelectClause = $query->getSelect();
		$select = str_contains($column, '.') ? $column : '`' . $object->modelDescription()->getTable() . '`.' . $column;
		$query->select("DISTINCT $select");
		$query->limit((string)$limit);
		$result = self::find($query, false);
		$query->select($originalSelectClause);
		return $result;
	}

	public function __call($name, $arguments): mixed
	{
		if (str_starts_with($name, 'get')) {
			$name = substr($name, 3);
			$propertyName = Helper::getSnakeCaseName($name);
			if (property_exists($this, $propertyName)) {
				return $this->{$propertyName};
			}
		} else if (str_starts_with($name, 'set') && !empty($arguments)) {
			$name = substr($name, 3);
			$propertyName = Helper::getSnakeCaseName($name);
			if (property_exists($this, $propertyName)) {
				$this->{$propertyName} = $arguments[0];
				return $this;
			}
		} else if (str_starts_with($name, 'add') && !empty($arguments)) {
			$name = substr($name, 3);
			$propertyName = Helper::getSnakeCaseName($name . 's');
			if (property_exists($this, $propertyName) && is_array($this->{$propertyName})) {
				$this->{$propertyName}[] = $arguments[0];
				return $this;
			}
		} else if (str_starts_with($name, 'remove') && !empty($arguments)) {
			$name = substr($name, 6);
			$propertyName = Helper::getSnakeCaseName($name . 's');
			if (property_exists($this, $propertyName) && is_array($this->{$propertyName})) {
				$collection = [];
				foreach ($this->{$propertyName} as $value) {
					if ($value->getId() != $arguments[0]->getId()) {
						$collection[] = $value;
					}
				}
				$this->{$propertyName} = $collection;
				return $this;
			}
		}
		throw new \Exception('Call to undefined method: "' . $name . '".');
	}

	public function toDto(): AbstractDTO
	{
		throw new \RuntimeException('The function must be implemented!');
	}

	public function save(): void
	{
		if (!$this->id) {
			$this->beforeInsert();
		} else {
			$this->beforeUpdate();
		}
		$this->beforeSave();
		$fields = [];
		$bindParams = [];
		$bindValues = [];
		$properties = $this->modelDescription()->getProperties();
		foreach ($properties as $property) {
			if (!ModelDescription::$DISABLE_AUTO_DATETIME && $this->modelDescription()->isAutoDatetime() && in_array($property, ['created_at', 'updated_at', 'deleted_at'])) {
				if (!$this->id && $property === 'created_at' && !ModelDescription::$DISABLE_CREATED_AT) {
					$propertyValue = Helper::getPropertyValue($this, $property);
					$datetime = Helper::toString($propertyValue);
					if (empty($datetime)) {
						$datetime = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
					}
					$fields[] = Helper::getSnakeCaseName($property);;
					$bindParams[] = ':created_at';
					$bindValues[':created_at'] = $datetime;
				} else if ($this->id && $property === 'updated_at' && !ModelDescription::$DISABLE_UPDATED_AT) {
					$propertyValue = Helper::getPropertyValue($this, $property);
					$datetime = Helper::toString($propertyValue);
					if (empty($datetime)) {
						$datetime = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
					}
					$fields[] = Helper::getSnakeCaseName($property);;
					$bindParams[] = ':updated_at';
					$bindValues[':updated_at'] = $datetime;
				} else if ($property === 'deleted_at' && !ModelDescription::$DISABLE_DELETED_AT) {
					$fields[] = Helper::getSnakeCaseName($property);;
					$bindParams[] = ':deleted_at';
					$bindValues[':deleted_at'] = null;
				}
			} else if ($property !== 'id') {
				$fields[] = Helper::getSnakeCaseName($property);
				$bindParams[] = ":{$property}";
				$propertyValue = Helper::getPropertyValue($this, $property);
				if (!is_null($propertyValue) && !ModelDescription::$DISABLE_AUTO_ENCRYPTION && in_array($property, $this->modelDescription()->getEncryptedFields())) {
					$propertyValue = Security::encrypt($propertyValue);
				}
				$bindValues[":{$property}"] = $propertyValue === null ? null : Helper::toString($propertyValue);
			}
		}
		if (empty($bindValues)) {
			throw new \RuntimeException('Unable to save this object!');
		}
		if ($this->id) {
			$updateBindParams = [];
			foreach ($fields as $index => $field) {
				$updateBindParams[] = "$field = $bindParams[$index]";
			}
			$bindValues[':id'] = $this->id;
			$updateSQL = 'UPDATE ' . $this->modelDescription()->getTable() . ' SET ' . implode(', ', $updateBindParams) . ' WHERE id = :id ;';
			DataBase::executeQuery($updateSQL, $bindValues);
		} else {
			$insertSQL = 'INSERT INTO ' . $this->modelDescription()->getTable() . ' (' . implode(', ', $fields) . ') VALUES(' . implode(',', $bindParams) . ');';
			DataBase::executeQuery($insertSQL, $bindValues);
			$this->id = DataBase::getInstance()->lastInsertId('id');
		}
	}

	protected function beforeInsert(): void
	{
		// You must override this method to use it.
	}

	protected function beforeUpdate(): void
	{
		// You must override this method to use it.
	}

	protected function beforeSave(): void
	{
		// You must override this method to use it.
	}

	public function delete(): void
	{
		if (!empty($this->id)) {
			$this->beforeDelete();
			if (
				!ModelDescription::$DISABLE_AUTO_DATETIME &&
				!ModelDescription::$DISABLE_DELETED_AT &&
				property_exists($this, 'deleted_at') &&
				$this->modelDescription()->isAutoDatetime() &&
				$this->modelDescription()->isSoftDelete()
			) {
				$deleteSQL = 'UPDATE ' . $this->modelDescription()->getTable() . ' SET deleted_at = :deleted_at WHERE id = :id ;';
				DataBase::executeQuery($deleteSQL, [':id' => $this->id, ':deleted_at' => (new \Datetime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')]);
			} else {
				DataBase::executeQuery('DELETE FROM ' . $this->modelDescription()->getTable() . ' WHERE id = :id ;', [':id' => $this->id]);
			}
		}
	}

	protected function beforeDelete(): void
	{
		// You must override this method to use it.
	}
}