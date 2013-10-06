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

	/**
	 * @var string
	 */
	protected $indepedentClassName;

	public function __construct($objectId, $indepedentClassName = null)
	{
		$this->objectId = $objectId;
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
				if (isset($notifiables[$methodName]) && is_array($notifiables[$methodName]))
				{
					foreach ($notifiables[$methodName] as $notifiableMethodName)
					{
						if (is_callable(array($object, $notifiableMethodName)))
						{
							$notifiableResult = $object->$notifiableMethodName();
							dabros::getRemoteObjectManager()->notify($this->objectId, $notifiableMethodName, $notifiableResult);
						}
					}
				}
			}
		}
		return $result;
	}

	public function _getObject()
	{
		if (is_null($this->indepedentClassName))
		{
			$object = dabros::getRemoteObjectManager()->getObject($this->objectId);
		}
		else
		{
			$object = dabros::getRemoteObjectManager()->getIndepedentObject($this->indepedentClassName, $this->objectId);
		}
		return $object;
	}

	public function _getObjectId()
	{
		return $this->objectId;
	}

	public function _getObjectInfo()
	{
		$objectInfo = array(
			'__ros__' => true,
			'objectId' => $this->objectId,
			'methods' => array(),
		);
		if (!is_null($this->indepedentClassName))
        {
            $objectInfo['indepedentClassName'] = $this->indepedentClassName;
        }
        
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
