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
require_once 'RemoteObjectException.php';
require_once 'DbStorageInterface.php';
require_once 'PdoStorage.php';

/**
 * Менеджер удаленно используемых объектов
 */
class RemoteObjectManager
{

	/**
	 * Настройки
	 * @var array
	 */
	private $config;

	/**
	 * Интерфейс доступа к базе данных хранилаща удаленно используемых объектов
	 * @var DbStorageInterface
	 */
	private $storage;

	/**
	 * Создает объект
	 * @param mixed $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
		if ($config['db'] instanceof DbStorageInterface)
		{
			$this->storage = $config['db'];
		}
		else
		{
			$this->storage = dabros::createComponent($config['db'], 'PdoStorage');
		}
	}

	/**
	 * Деструктор.
	 * Сохраняет все использовааные объекты в базу данных
	 */
	public function __destruct()
	{
		foreach ($this->objectCache as $objectId => $object)
		{
			$this->storage->updateObject($objectId, $object);
		}
	}

	/**
	 * Массив используемых объектов
	 * @var array
	 */
	private $objectCache = array();

	/**
	 * Создает удаленно управляемый объект и возвращает прокси для взаимодействия с ним
	 * @param string $className
	 * @param string $objectId
	 * @return RemoteObjectProxy
	 */
	public function createObject($className, $objectId = null, $arguments = null)
	{
		$rc = new ReflectionClass($className);
		$object = $rc->newInstanceArgs($arguments);
		$objectId = $this->storage->saveObject($objectId, ObjectType::OBJECT, $object);
		if (!is_null($objectId))
		{
			$this->objectCache[$objectId] = $object;
			return new RemoteObjectProxy($objectId);
		}
		return null;
	}

	/**
	 * Создает синглтон - удаленно управляемый объект и возвращает прокси для взаимодействия с ним
	 * @param string $className
	 * @return RemoteObjectProxy
	 */
	public function getSingleton($className)
	{
		$objectId = 'singleton_' . $className;
		$object = $this->storage->restoreObject($objectId);
		if (!is_null($object))
		{
			$this->objectCache[$objectId] = $object;
			return new RemoteObjectProxy($objectId);
		}
		else
		{
			$object = new $className();
			$objectId = $this->storage->saveObject($objectId, ObjectType::SINGLETON, $object);
			if (!is_null($objectId))
			{
				$this->objectCache[$objectId] = $object;
				return new RemoteObjectProxy($objectId);
			}
			return null;
		}
	}

	/**
	 * Создает сесионный синглтон - удаленно управляемый объект и возвращает прокси для взаимодействия с ним
	 * @param string $className
	 * @return RemoteObjectProxy
	 */
	public function getSessionSingleton($className)
	{
		$objectId = null;
		if (!isset($_SESSION)) session_start();
		if (isset($_SESSION[$className]))
		{
			$objectId = $_SESSION[$className];
			if (is_null($this->getObject($objectId)))
			{
				$objectId = null;
				unset($_SESSION[$className]);
			}
		}
		$singleton = null;
		if (!is_null($objectId))
		{
			$singleton = new RemoteObjectProxy($objectId);
		}
		else
		{
			$object = new $className();
			$objectId = $this->storage->saveObject(null, ObjectType::SESSION_SINGLETON, $object);
			if (!is_null($objectId))
			{
				$this->objectCache[$objectId] = $object;
				$_SESSION[$className] = $objectId;
				$singleton = new RemoteObjectProxy($objectId);
			}
		}
		return $singleton;
	}

	/**
	 * Загружает из базы данных объект по его идентификатору
	 * @param string $objectId
	 * @return RemoteObjectProxy
	 */
	public function getObjectProxy($objectId)
	{
		if (is_null($this->getObject($objectId)))
		{
			return null;
		}
		return new RemoteObjectProxy($objectId);
	}

	/**
	 * Загружает из базы данных объект по его идентификатору
	 * @param string $objectId
	 * @return StdObj
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
			if (!is_null($object)) $this->objectCache[$objectId] = $object;
		}
		return $object;
	}

	private $indepedentObjectCache = array();

	/**
	 * Возвращает независимый удаленно управляемый объект
	 * Объект называется независимым, потому что его состояние хранится не в общем хранилище удаленно управляемых объектов
	 * По сути, создается объект заданного класса, в коструктор которого передается идентификатор объекта
	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return mixed
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
