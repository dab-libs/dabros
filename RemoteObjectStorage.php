<?php
/**
 * Dabros version 0.1.0
 * RPC Library for PHP & JavaScript
 *
 * @author  Dmitry Bystrov <uncle.demian@gmail.com>, 2013
 * @source  https://github.com/dab-libs/dabros
 * @date    2013-03-08
 * @license Lesser GPL licenses (http://www.gnu.org/copyleft/lesser.html)
 */

require_once 'DbStorageInterface.php';
require_once 'PdoStorage.php';

/**
 * Хранилаще удаленно используемых объектов
 */
class RemoteObjectStorage implements RemoteObjectStorageInterface
{

	/**
	 * @var mixed
	 */
	private $dbStorage;

	/**
	 * Создает объект
	 * @param mixed $connection - объект PDO или массив со следующими полями:
	 * <ul>
	 * <li>connectionString - Имя источника данных или DSN, содержащее информацию, необходимую для подключения к базе данных
	 * <li>username - Имя пользователя для строки DSN. Необязательно. По умолчанию: ""
	 * <li>password - Пароль для строки DSN. Необязательно. По умолчанию: ""
	 * <li>options - Массив специфичных для драйвера настроек подключения ключ=>значение. Необязательно. По умолчанию: array()
	 * <li>table - Название таблицы. Необязательно. По умолчанию: "dabros_storage"
	 * </ul>
	 * @param string $tableName - имя таблицы для хранания удаленно управляемых объектов
	 */
	public function __construct($connection, $tableName = null)
	{
		if ($connection instanceof DbStorageInterface)
		{
			$this->dbStorage = $connection;
		}
		else
		{
			$this->dbStorage = new PdoStorage($connection, $tableName);
		}
	}

	public function __destruct()
	{
		foreach ($this->objectCache as $objectId => $object)
		{
			$this->dbStorage->updateObject($object, $objectId);
		}
	}

	private $objectCache = array();

	/**
	 *
	 * @param string $className
	 * @param int $objectId
	 * @return RemoteObjectProxy
	 */
	public function createObject($className, $objectId = -1)
	{
		$object = new $className();
		$objectId = $this->dbStorage->saveObject($object, $objectId);
		$this->objectCache[$objectId] = $object;
		return $objectId;
	}

	/**
	 *
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getObject($objectId)
	{
		$object = null;
		if (isset($this->objectCache[$objectId]))
		{
			$object = $this->objectCache[$objectId];
		}
		else
		{
			$object = $this->dbStorage->restoreObject($objectId);
			if (!is_null($object))
			{
				$this->objectCache[$objectId] = $object;
			}
		}
		return $object;
	}

	private $indepedentObjectCache = array();

	/**
	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getIndepedentObject($className, $objectId)
	{
		if (!isset($this->indepedentObjectCache[$className])) $this->indepedentObjectCache[$className] = array();
		if (!isset($this->indepedentObjectCache[$className][$objectId]))
		{
			$object = new $className($objectId);
			$this->indepedentObjectCache[$className][$objectId] = $object;
		}
		return $this->indepedentObjectCache[$className][$objectId];
	}
}
