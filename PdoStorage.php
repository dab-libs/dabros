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
 * PDO-хранилаще удаленно используемых объектов
 */
class PdoStorage implements RemoteStorageInterface
{

    /**
     * �?нформация об ошибке последнего запроса
     * @var array
     */
    private $errorInfo;

    /**
     * �?мя таблицы для хранания удаленно управляемых объектов
     * @var string
     */
    private $objectTableName = 'dabros_objects';

    /**
     * Создает объект
     * @param mixed $connection - объект PDO или массив со следующими полями:
     * <ul>
     * <li>table - имя таблицы для хранания удаленно управляемых объектов
     * </ul>
     */
    public function __construct( $connection )
    {
        if ( isset( $connection[ 'table' ] ) )
            $this->objectTableName = $connection[ 'table' ];
        $this->createTable();
    }

    /**
     * Создает таблицы для хранания удаленно управляемых объектов
     */
    private function createTable()
    {
        try
        {
            $query = <<<QUERY
SHOW TABLES LIKE '{$this->objectTableName}'
QUERY;
            $sqlStatement = dabros::getPdo()->query( $query );
            $this->errorInfo = dabros::getPdo()->errorInfo();
            $rowCount = $sqlStatement->rowCount();
            $sqlStatement->closeCursor();
            if ( $rowCount != 1 )
            {
                $this->executeCreatingTableQuery();
            }
        }
        catch ( Exception $e )
        {
            $this->executeCreatingTableQuery();
        }
    }

    /**
     * Выполняет запрос создания таблицы
     */
    private function executeCreatingTableQuery()
    {
        $query = <<<QUERY
CREATE TABLE `{$this->objectTableName}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(128) DEFAULT NULL,
  `type` enum('VALUE','OBJECT','SINGLETON','SESSION_SINGLETON') DEFAULT NULL,
  `data` blob NOT NULL,
  `textData` text,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
QUERY;
        dabros::getPdo()->exec( $query );
        $this->errorInfo = dabros::getPdo()->errorInfo();
    }

    /**
     * Сохраняет объект в базе данных
     * @param string $objectKey - Если null, то ключ будет создан автоматически
     * @param string $type
     * @param object $object
     * @param array $options
     * @return string - Ключ, с которым соранен объект
     */
    public function saveObject( $objectKey, $type, $object, $options = array( ) )
    {
        $textData = serialize( $object );
        $params = array(
            ':key'      => $objectKey,
            ':type'     => $type,
            ':data'     => base64_encode( $textData ),
            ':textData' => $textData,
        );
        $query = <<<QUERY
INSERT INTO `{$this->objectTableName}` (
	`key`,
	`type`,
	`data`,
	`textData`,
	`created`,
	`modified`
) VALUES (
	:key,
	:type,
	:data,
	:textData,
	NOW(),
	NOW()
)
QUERY;
        $sqlStatement = dabros::getPdo()->prepare( $query );
        if ( $sqlStatement->execute( $params ) )
        {
            $sqlStatement->closeCursor();
            if ( is_null( $objectKey ) )
            {
                $objectKey = dabros::getPdo()->lastInsertId();
                $params = array(
                    ':id'  => $objectKey,
                    ':key' => $objectKey,
                );
                $query = <<<QUERY
UPDATE
	`{$this->objectTableName}`
SET
	`key` = :key
WHERE
	`id` = :id
QUERY;
                $sqlStatement = dabros::getPdo()->prepare( $query );
                if ( !$sqlStatement->execute( $params ) )
                {
                    $this->errorInfo = dabros::getPdo()->errorInfo();
                    $objectKey = null;
                }
                $sqlStatement->closeCursor();
            }
        }
        else
        {
            $this->errorInfo = dabros::getPdo()->errorInfo();
            $sqlStatement->closeCursor();
            $objectKey = null;
        }
        return $objectKey;
    }

    /**
     * Обновляет объект в базе данных
     * @param string $objectKey
     * @param object $object
     */
    public function updateObject( $objectKey, $object )
    {
        $textData = serialize( $object );
        $params = array(
            ':key'      => $objectKey,
            ':data'     => base64_encode( $textData ),
            ':textData' => $textData,
        );
        $query = <<<QUERY
UPDATE
	`{$this->objectTableName}`
SET
	`data` = :data,
	`textData` = :textData,
	`modified` = NOW()
WHERE
	`key` = :key
QUERY;
        $sqlStatement = dabros::getPdo()->prepare( $query );
        $sqlStatement->execute( $params );
        $this->errorInfo = dabros::getPdo()->errorInfo();
        $sqlStatement->closeCursor();
    }

    /**
     * Загружает объект их базе данных
     * @param string $objectKey
     * @return object
     */
    public function restoreObject( $objectKey )
    {
        $object = null;
        $params = array(
            ':key' => $objectKey,
        );
        $query = <<<QUERY
SELECT
	`data`
FROM
	`{$this->objectTableName}`
WHERE
	`key` = :key
QUERY;
        $sqlStatement = dabros::getPdo()->prepare( $query );
        $sqlStatement->execute( $params );
        $this->errorInfo = dabros::getPdo()->errorInfo();
        $queryResult = $sqlStatement->fetch( PDO::FETCH_ASSOC );
        if ( $queryResult )
        {
            $data = base64_decode( $queryResult[ 'data' ] );
            $object = unserialize( $data . ';' );
            $this->errorInfo = error_get_last();
            $object = ($object === false ? null : $object);
        }
        $sqlStatement->closeCursor();
        return $object;
    }

	/**
	 * Возвращает массив ключей удаленно используемых объектов,
	 * начинащихся с занного префикса
	 * @param string $objectKeyPrefix
	 * @param integer $offset
	 * @param integer $limit
	 * @return array
	 */
	public function getObjectKeys( $objectKeyPrefix, $offset, $limit )
	{
		$objectKeys = array();
		$params = array(
			':key' => $objectKeyPrefix,
			':offset' => $offset,
			':limit' => $limit,
		);
		$query = <<<QUERY
SELECT
	`key`
FROM
	`{$this->objectTableName}`
WHERE
	`key` like :key
ORDER BY
	`key` ASC
LIMIT
	:offset, :limit
QUERY;
		$sqlStatement = $this->pdo->prepare( $query );
		$sqlStatement->execute( $params );
		$this->errorInfo = $this->pdo->errorInfo();
		while ( $queryRow = $sqlStatement->fetch( PDO::FETCH_ASSOC ) )
		{
			$objectKeys[] = $queryRow[ 'key' ];
		}
		$sqlStatement->closeCursor();
		return $objectKeys;
	}

}
