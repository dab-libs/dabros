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

class RemoteObjectType
{

	const VALUE = 'VALUE';
	const OBJECT = 'OBJECT';
	const SINGLETON = 'SINGLETON';
	const SESSION_SINGLETON = 'SESSION_SINGLETON';
	const INDEPEDENT = 'INDEPEDENT';

}

/**
 * Интерфейс доступа к базе данных хранилаща удаленно используемых объектов
 */
interface RemoteStorageInterface
{
	/**
	 * Сохраняет объект в базе данных
	 * @param string $objectKey - Если null, то ключ будет создан автоматически
	 * @param string $type
	 * @param object $object
	 * @param array $options
	 * @return string - Ключ, с которым соранен объект
	 */
	public function saveObject($objectKey, $type, $object, $options = array());

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

	/**
	 * Возвращает массив ключей удаленно используемых объектов,
	 * начинащихся с занного префикса
	 * @param string $objectKeyPrefix
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */
	public function getObjectKeys($objectKeyPrefix, $offset, $limit);
}
