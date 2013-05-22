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

require_once 'RemoteObjectService.php';
require_once 'RemoteObjectException.php';
require_once 'DbStorageInterface.php';
require_once 'PdoStorage.php';
require_once 'SessionStorage.php';

/**
 * Менеджер удаленно используемых объектов
 */
class RemoteObjectManager
{

	/**
	 * Интерфейс доступа к базе данных хранилаща удаленно используемых объектов
	 *
	 * @var DbStorageInterface
	 */
	private $applicationStorage;

	/**
	 * Интерфейс доступа для хранения удаленно используемых объектов в сессии
	 *
	 * @var SessionStorage
	 */
	private $sessionStorage;

	/**
	 * Создает объект
	 * @param mixed $storage
	 */
	public function __construct($storage)
	{
		if ($storage instanceof DbStorageInterface)
		{
			$this->applicationStorage = $storage;
		}
		else
		{
			$this->applicationStorage = new PdoStorage($storage);
		}
		$this->sessionStorage = new SessionStorage();
	}

	public function __destruct()
	{
		foreach ($this->applicationObjectCache as $objectId => $object)
		{
			$this->applicationStorage->updateObject($object, $objectId);
		}
	}

	private $applicationObjectCache = array();

	public function createApplicationObject($className, $objectId = null)
	{
		$object = new $className();
		$objectId = $this->applicationStorage->saveObject($object, $objectId);
		$this->applicationObjectCache[$objectId] = $object;
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::APPLICATION_OBJECT);
	}

	public function getApplicationSingleton($className)
	{
		$request = array('id' => 0, 'objectId' => 1);
		$request = (object) $request;
		$remoteObjectService = $this->getRemoteObject($request, $errors);
		/* @var $remoteObjectService RemoteObjectService */
		$objectId = $remoteObjectService->_getAplicationSingletonId($className);
		if ($objectId === false)
		{
			$object = new $className();
			$objectId = $this->applicationStorage->saveObject($object);
			$remoteObjectService->_setAplicationSingletonId($className, $objectId);
		}
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::APPLICATION_SINGLETON);
	}

	public function getApplicationObject($objectId)
	{
		$object = null;
		if (isset($this->applicationObjectCache[$objectId]))
		{
			$object = $this->applicationObjectCache[$objectId];
		}
		else
		{
			$object = $this->applicationStorage->restoreObject($objectId);
			if (!is_null($object)) $this->applicationObjectCache[$objectId] = $object;
		}
		return $object;
	}

	public function createSessionObject($className, $objectId = null)
	{
		$object = new $className();
		$objectId = $this->sessionStorage->saveObject($object, $objectId);
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::SESSION_OBJECT);
	}

	public function getSessionSingleton($className)
	{
		$objectId = $this->sessionStorage->getSingletonId($className);
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::SESSION_SINGLETON);
	}

	public function getSessionObject($objectId)
	{
		$object = $this->sessionStorage->restoreObject($objectId);
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

	protected $sessionNotifications = array();

	public function notifySession($objectId, $methodName, $result)
	{
		$this->sessionNotifications[] = array(
			'objectId' => $objectId, 'methodName' => $methodName, 'result' => $result,
		);
	}

	public function notifyApplication($objectId, $methodName, $result)
	{

	}

	public function handle()
	{
		$requestList = json_decode($_POST['request']);
		if (is_null($requestList))
		{

		}
		else
		{
			if (!is_array($requestList))
			{
				$requestList = array($requestList);
			}
			$results = array();
			foreach ($requestList as $request)
			{
				$results[] = $this->handleRequest($request);
			}
			if (!empty($this->sessionNotifications))
			{
				$results[] = $this->createResult(-1, $this->sessionNotifications);
			}
			echo json_encode($results);
		}
	}

	protected function handleRequest($request)
	{
		$errors = array();
		if (!is_null($object = $this->getRemoteObject($request, $errors)))
		{
			$params = (isset($request->params) ? $request->params : array());
			$result = call_user_func_array(array($object, $request->method), $params);
			$result = $this->createResult($request->id, $result);
		}
		else
		{
			$result = $this->createError($errors);
		}
		return $result;
	}

	protected function createResult($requestId, $result)
	{
		return array(
			'result' => $this->encodeResult($result),
			'id' => $requestId
		);
	}

	protected function encodeResult($result)
	{
		if (is_array($result))
		{
			$encoded = array();
			foreach ($result as $key => $value)
			{
				$encoded[$key] = $this->encodeResult($value);
			}
			return $encoded;
		}
		elseif (is_object($result) && $result instanceof RemoteObjectProxy)
		{
			$objectInfo = $result->_getObjectInfo();
			$this->encodePreloadedData($result, 'preloaded', $objectInfo);
			$this->encodePreloadedData($result, 'consts', $objectInfo);
			return $objectInfo;
		}
		elseif (is_object($result) && $result instanceof DateTime)
		{
			$tz = $result->getTimezone();
			$result->setTimezone(new DateTimeZone('GMT'));
			$encoded = array(
				'__date__' => $result->format('D, d M Y H:i:s') . ' GMT',
			);
			$result->setTimezone($tz);
			return $encoded;
		}
		else
		{
			return $result;
		}
	}

	protected function encodePreloadedData($object, $preloadedType, &$objectInfo)
	{
		try
		{
			$preloadedMethod = '_' . $preloadedType;
			$preloadedData = $object->$preloadedMethod();
		}
		catch (Exception $exc)
		{

		}
		if (is_array($preloadedData))
		{
			$objectInfo[$preloadedType] = array();
			foreach ($preloadedData as $method)
			{
				$preloadedResult = $object->$method();
				$objectInfo[$preloadedType][$method] = $this->encodeResult($preloadedResult);
			}
		}
	}

	protected function createError()
	{

	}

	protected function getRemoteObject($request, $errors)
	{
		$object = null;
		if ($request->method{0} == '_')
		{

		}
		elseif (isset($request->indepedentClassName))
		{
			$object = new RemoteObjectProxy($objectId, RemoteObjectProxy::INDEPEDENT, $request->indepedentClassName);
		}
		elseif ($request->objectId > 0)
		{
			$object = $this->getApplicationObject($request->objectId);
			if (is_null($object) && $request->objectId == 1)
			{
				$request->objectId = $this->createApplicationObject('RemoteObjectService', $request->objectId);
				$object = $this->storage->getObject($request->objectId);
			}
		}
		else
		{
			$object = $this->getSessionObject($request->objectId);
		}
		return $object;
	}

}
