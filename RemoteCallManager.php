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

/**
 * Менеджер удаленно вызовов
 */
class RemoteCallManager
{

	/**
	 * Настройки
	 * @var array
	 */
	private $config;

	/**
	 * Создает объект
	 * @param mixed $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
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

	public function handleRequest($request)
	{
		$errors = array();
		if ($request->objectId == 0 && $request->method == 'getFacade')
		{
			$result = dabros::getRemoteUserSession()->getSessionFacade();
			$result = $this->createResult($request->id, $result);
		}
		elseif (!is_null($object = $this->getRemoteObject($request, $errors)))
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
			$object = new RemoteObjectProxy($objectId, RemoteObjectType::INDEPEDENT, $request->indepedentClassName);
		}
		else
		{
			$object = dabros::getRemoteObjectManager()->getObjectProxy($request->objectId);
		}
		return $object;
	}

}
