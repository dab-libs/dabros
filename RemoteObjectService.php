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
 * Сервис создания сесионых фасадов
 */
class RemoteObjectService
{
	public function getSessionFacade($className)
	{
		return dabros::getRemoteObjectManager()->getSessionSingleton($className);
	}

	protected $applicationSingletons = array();

	public function _getAplicationSingletonId($className)
	{
		if (isset($this->applicationSingletons[$className]))
		{
			return $this->applicationSingletons[$className];
		}
		return null;
	}

	public function _setAplicationSingletonId($className, $objectId)
	{
		$this->applicationSingletons[$className] = $objectId;
	}
}
