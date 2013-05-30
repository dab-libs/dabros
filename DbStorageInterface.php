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
	 * @param string $objectKey - Если null, то ключ будет создан автоматически
	 * @param object $object
	 * @param array $options
	 * @return string - Ключ, с которым соранен объект
	 */
	public function saveObject($objectKey, $object, $options = array());

	/**
	 * Обновляет объект в базе данных
	 * @param string $objectKey
	 * @param object $object
	 */
	public function updateObject($objectKey, $object);

	/**
	 * Загружает объект их базе данных
	 * @param string $objectKey
	 * @return object
	 */
	public function restoreObject($objectKey);
}
