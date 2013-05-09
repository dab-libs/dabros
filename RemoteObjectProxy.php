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
 * Серверный заменитель (Server proxy) удаленно используемого объекта
 */
class RemoteObjectProxy
{

	/**
	 * @var integer
	 */
	protected $objectId;

	const APPLICATION_OBJECT = 'APPLICATION_OBJECT';
	const SESSION_OBJECT = 'SESSION_OBJECT';
	const SESSION_SINGLETON = 'SESSION_SINGLETON';
	const APPLICATION_SINGLETON = 'APPLICATION_SINGLETON';
	const INDEPEDENT = 'INDEPEDENT';

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $indepedentClassName;

	public function __construct($objectId, $type, $indepedentClassName = '')
	{
		$this->objectId = $objectId;
		$this->type = $type;
		$this->indepedentClassName = $indepedentClassName;
	}

	public function __call($methodName, $arguments)
	{
		$result = false;
		$object = $this->_getObject();
		if (is_callable(array($object, $methodName)))
		{
			$result = call_user_func_array(array($object, $methodName), $arguments);
			if (is_callable(array($object, '_notifiables')))
			{
				$notifiables = $object->_notifiables();
				if (isset($notifiables[$methodName]) && is_array($notifiables[$methodName]) &&
						($this->type == self::SESSION_SINGLETON || $this->type == self::APPLICATION_SINGLETON))
				{
					foreach ($notifiables[$methodName] as $notifiableMethodName)
					{
						if (is_callable(array($object, $notifiableMethodName)))
						{
							$notifiableResult = $object->$notifiableMethodName();
							if ($this->type == self::SESSION_SINGLETON)
							{
								dabros::getRemoteObjectManager()->notifySession($this->objectId, $notifiableMethodName, $notifiableResult);
							}
							elseif ($this->type == self::APPLICATION_SINGLETON)
							{
								dabros::getRemoteObjectManager()->notifyApplication($this->objectId, $notifiableMethodName, $notifiableResult);
							}
						}
					}
				}
			}
		}
		return $result;
	}

	public function _getObject()
	{
		if ($this->type == self::INDEPEDENT)
		{
			$object = dabros::getRemoteObjectManager()->getIndepedentObject($this->indepedentClassName, $this->objectId);
		}
		elseif ($this->type == self::APPLICATION_SINGLETON || $this->type == self::APPLICATION_OBJECT)
		{
			$object = dabros::getRemoteObjectManager()->getApplicationObject($this->objectId);
		}
		elseif ($this->type == self::SESSION_SINGLETON || $this->type == self::SESSION_OBJECT)
		{
			$object = dabros::getRemoteObjectManager()->getSessionObject($this->objectId);
		}
		return $object;
	}

	public function _getObjectInfo()
	{
		$objectInfo = array(
			'__ros__' => $this->type,
			'objectId' => $this->objectId,
			'methods' => array(),
		);
		$object = $this->_getObject();
		$objectClass = new ReflectionClass($object);
		$objectMethods = $objectClass->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($objectMethods as $method)
		{
			if ($method->name{0} != '_')
			{
				$objectInfo['methods'][] = $method->name;
			}
		}
		return $objectInfo;
	}

}
