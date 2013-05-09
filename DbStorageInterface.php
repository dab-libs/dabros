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
 * Интерфейс доступа к базе данных хранилаща удаленно используемых объектов
 */
interface DbStorageInterface
{

	/**
	 * Сохраняет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 * @return int - Идентификатор объекта
	 */
	public function saveObject($object, $objectId = null);

	/**
	 * Обновляет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 */
	public function updateObject($object, $objectId);

	/**
	 * Загружает объект их базе данных
	 * @param int $objectId
	 * @return object
	 */
	public function restoreObject($objectId);
}
