<?php
/**
 * Dabros version 0.0.1
 * RPC Library for PHP & JavaScript
 *
 * @author  Dmitry Bystrov <uncle.demian@gmail.com>, 2013
 * @source  https://github.com/dab-libs/dabros
 * @date    2013-03-08
 * @license Lesser GPL licenses (http://www.gnu.org/copyleft/lesser.html)
 */

/**
 * Интерфейс хранилаща удаленно используемых объектов
 */
interface RemoteObjectStorageInterface
{
	/**
	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function createObject($className, $objectId = -1);

	/**
	 *
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getObject($objectId);

	/**
	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getIndepedentObject($className, $objectId);

}
