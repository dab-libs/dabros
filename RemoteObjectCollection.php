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
 * Коллекция удаленно используемых объектов
 */
class RemoteObjectCollection
{

	private $objectKeyPrefix;
	private $nextIndex = 0;

	public function __construct()
	{
		$this->objectKeyPrefix = $this->generateObjectKeyPrefix();
	}

	public function createItem( $className )
	{
		$objectKey = $this->objectKeyPrefix . $this->nextIndex++;
		return dabros::getRemoteObjectManager()->createObject( $className, $objectKey );
	}

	public function requestItems( $offset, $limit )
	{
		return dabros::getRemoteObjectManager()->getObjectProxyArray( $this->objectKeyPrefix, $offset, $limit );
	}

	private function generateObjectKeyPrefix()
	{
		$now = new DateTime();
		$nowStr = $now->format( "Yms_His" );
		$rand = mt_rand();
		return __CLASS__ . '_' . $nowStr . '_' . $rand . '_';
	}

}
