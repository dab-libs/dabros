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
 * Хранилище сессионных удаленно используемых объектов
 */
class SessionStorage
{

	private $keyPrefix = 'dabros_';

	/**
	 * Создает объект
	 */
	public function __construct()
	{
		session_start();
	}

	/**
	 * Сохраняет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 * @return int - Идентификатор объекта
	 */
	public function saveObject($object, $objectId = null)
	{
		if (!isset($_SESSION[$this->keyPrefix . 'lastObjectId'])) $_SESSION[$this->keyPrefix . 'lastObjectId'] = 0;
		if (is_null($objectId))
		{
			$objectId = $_SESSION[$this->keyPrefix . 'maxObjectId'] - 1;
		}
		$_SESSION[$this->keyPrefix . 'id' . $objectId] = $object;
		if ($objectId < $_SESSION[$this->keyPrefix . 'maxObjectId'])
		{
			$_SESSION[$this->keyPrefix . 'maxObjectId'] = $objectId;
		}
		return $objectId;
	}

	/**
	 * Обновляет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 */
	public function updateObject($object, $objectId)
	{
		$_SESSION[$this->keyPrefix . 'id' . $objectId] = $object;
	}

	/**
	 * Загружает объект их базе данных
	 * @param int $objectId
	 * @return object
	 */
	public function restoreObject($objectId)
	{
		return $_SESSION[$this->keyPrefix . 'id' . $objectId];
	}

	/**
	 * Загружает объект их базе данных
	 * @param int $objectId
	 * @return object
	 */
	public function getSingletonId($className)
	{
		if (!isset($_SESSION[$this->keyPrefix . $className]))
		{
			$object = new $className();
			$_SESSION[$this->keyPrefix . $className] = $this->saveObject($object);
		}
		return $_SESSION[$this->keyPrefix . $className];
	}
}
