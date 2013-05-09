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

require_once 'PdoStorage.php';
require_once 'SessionStorage.php';

/**
 * Хранилаще удаленно используемых объектов
 */
class RemoteObjectStorage
{

	/**
	 * Интерфейс доступа к базе данных хранилаща удаленно используемых объектов
	 *
	 * @var DbStorageInterface
	 */
	private $dbStorage;

	/**
	 * Интерфейс доступа для хранения удаленно используемых объектов в сессии
	 *
	 * @var DbStorageInterface
	 */
	private $sessionStorage;

	/**
	 * Создает объект
	 * @param mixed $dbConnection - объект PDO или массив со следующими полями:
	 * <ul>
	 * <li>connectionString - Имя источника данных или DSN, содержащее информацию, необходимую для подключения к базе данных
	 * <li>username - Имя пользователя для строки DSN. Необязательно. По умолчанию: ""
	 * <li>password - Пароль для строки DSN. Необязательно. По умолчанию: ""
	 * <li>options - Массив специфичных для драйвера настроек подключения ключ=>значение. Необязательно. По умолчанию: array()
	 * <li>table - Название таблицы. Необязательно. По умолчанию: "dabros_storage"
	 * </ul>
	 */
	public function __construct($dbConnection)
	{
		if ($dbConnection instanceof DbStorageInterface)
		{
			$this->dbStorage = $dbConnection;
		}
		else
		{
			$this->dbStorage = new PdoStorage($dbConnection, $dbConnection['table']);
		}
		$this->sessionStorage = new SessionStorage();
		$this->setSessionMode();
	}

	public function __destruct()
	{
		foreach ($this->objectCache as $objectId => $object)
		{
			$this->storage->updateObject($object, $objectId);
		}
	}

	private $objectCache = array();

	/**
	 *
	 * @param string $className
	 * @param int $objectId
	 * @return RemoteObjectProxy
	 */
	public function createApplicationObject($className, $objectId = null)
	{
		$object = new $className();
		$objectId = $this->storage->saveObject($object, $objectId);
		$this->objectCache[$objectId] = $object;
		return $objectId;
	}

	/**
	 *
	 * @param string $className
	 * @param int $objectId
	 * @return RemoteObjectProxy
	 */
	public function createObject($className, $objectId = null)
	{
		$object = new $className();
		$objectId = $this->storage->saveObject($object, $objectId);
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
			$object = $this->storage->restoreObject($objectId);
			if (!is_null($object))
			{
				$this->objectCache[$objectId] = $object;
			}
		}
		return $object;
	}
}
