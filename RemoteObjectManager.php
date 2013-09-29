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
	 * @var RemoteStorageInterface
	 */
	private $storage;

	/**
	 * Создает объект
	 * @param mixed $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
		if ($config['storage'] instanceof DbStorageInterface)
		{
			$this->storage = $config['storage'];
		}
		else
		{
			$this->storage = dabros::createComponent($config['storage'], 'PdoStorage');
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
		$objectId = $this->storage->saveObject($objectId, RemoteObjectType::OBJECT, $object);
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
			$objectId = $this->storage->saveObject($objectId, RemoteObjectType::SINGLETON, $object);
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
			$objectId = $this->storage->saveObject(null, RemoteObjectType::SESSION_SINGLETON, $object);
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
	 * Возвращает массив удаленно используемых объектов,
	 * начинащихся с занного префикса
	 * @param string $objectKeyPrefix
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */
	public function getObjectProxyArray($objectKeyPrefix, $offset, $limit)
	{
		$objectProxies = array();
		$objectKeyArray = $this->storage->getObjectKeys($objectKeyPrefix, $offset, $limit);
		foreach ( $objectKeyArray as $objectKey )
		{
			$objectProxies[] = new RemoteObjectProxy($objectKey);
		}
		return $objectProxies;
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
	 * Возвращает заменитель независимый удаленно управляемого объекта	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getIndepedentObjectProxy($className, $objectId)
	{
		return new RemoteObjectProxy($objectId, $className);
	}

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
