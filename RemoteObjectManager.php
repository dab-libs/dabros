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

/**
 * Менеджер удаленно используемых объектов
 */
class RemoteObjectManager
{

	/**
	 * Хранилище объектов
	 * @var RemoteObjectStorageInterface
	 */
	protected $storage;

	/**
	 * Создает объект
	 * @param RemoteObjectStorageInterface $storage
	 * @throws RemoteObjectException
	 */
	public function __construct($storage)
	{
		if (is_array($storage))
		{
			$this->storage = new RemoteObjectStorage($storage);
		}
		elseif ($storage instanceof RemoteObjectStorageInterface)
		{
			$this->storage = $storage;
		}
		else
		{
			throw new RemoteObjectException('Remote Object Storage is not defined');
		}
	}

	/**
	 * @return RemoteObjectStorageInterface
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	public function createObject($className)
	{
		$objectId = $this->storage->createObject($className);
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::SIMPLE);
	}

	public function createIndepedentObject($className, $objectId)
	{
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::INDEPEDENT, $className);
	}

	public function getSessionSingleton($className)
	{
		if (isset($_SESSION[$className . 'Id']))
		{
			$objectId = $_SESSION[$className . 'Id'];
			$object = $this->storage->getObject($objectId);
		}
		if (!isset($object) || is_null($object))
		{
			$objectId = $this->storage->createObject($className);
			$_SESSION[$className . 'Id'] = $objectId;
		}
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::SESSION_SINGLETON);
	}

	public function getUserSingleton($className)
	{
		if (isset($_SESSION[$className . 'Id']))
		{
			$objectId = $_SESSION[$className . 'Id'];
		}
		else
		{
			$objectId = $this->storage->createObject($className);
			$_SESSION[$className . 'Id'] = $objectId;
		}
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::SESSION_SINGLETON);
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
			$objectId = $this->storage->createObject($className);
			$remoteObjectService->_setAplicationSingletonId($className, $objectId);
		}
		return new RemoteObjectProxy($objectId, RemoteObjectProxy::APPLICATION_SINGLETON);
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
			$objectInfo = $result->getObjectInfo();
			$this->encodePreloadedData($result, 'preloaded', $objectInfo);
			$this->encodePreloadedData($result, 'consts', $objectInfo);
			return $objectInfo;
		}
		elseif (is_object($result) && $result instanceof DateTime)
		{
			$encoded = array(
				'__date__' => $result->format('D, d M Y H:i:s O'),
			);
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
		else
		{
			$object = $this->storage->getObject($request->objectId);
			if (is_null($object) && $request->objectId == 1)
			{
				$request->objectId = $this->storage->createObject('RemoteObjectService', $request->objectId);
				$object = $this->storage->getObject($request->objectId);
			}
		}
		return $object;
	}

}
