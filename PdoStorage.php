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
require_once 'DbStorageInterface.php';

/**
 * PDO-хранилаще удаленно используемых объектов
 */
class PdoStorage implements DbStorageInterface
{

	/**
	 * @var PDO
	 */
	private $pdo;

	/**
	 * Информация об ошибке последнего запроса
	 * @var array
	 */
	private $errorInfo;

	/**
	 * Имя таблицы для хранания удаленно управляемых объектов
	 * @var string
	 */
	private $objectTableName = 'dabros_objects';

	/**
	 * Создает объект
	 * @param mixed $connection - объект PDO или массив со следующими полями:
	 * <ul>
	 * <li>connectionString - Имя источника данных или DSN, содержащее информацию, необходимую для подключения к базе данных
	 * <li>username - Имя пользователя для строки DSN. Необязательно. По умолчанию: ""
	 * <li>password - Пароль для строки DSN. Необязательно. По умолчанию: ""
	 * <li>options - Массив специфичных для драйвера настроек подключения ключ=>значение. Необязательно. По умолчанию: array()
	 * <li>table - имя таблицы для хранания удаленно управляемых объектов
	 * </ul>
	 */
	public function __construct($connection)
	{
		if ($connection instanceof PDO)
		{
			$this->pdo = $connection;
		}
		else
		{
			$connectionString = $connection['connectionString'];
			$username = $connection['username'];
			$password = $connection['password'];
			$options = $connection['options'];
			$this->pdo = new PDO($connectionString, $username, $password, $options);
		}
		if (isset($connection['table'])) $this->objectTableName = $connection['table'];
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
			$sqlStatement = $this->pdo->query($query);
			$this->errorInfo = $this->pdo->errorInfo();
			$rowCount = $sqlStatement->rowCount();
			$sqlStatement->closeCursor();
			if ($rowCount != 1)
			{
				$this->ExecuteCreatingTableQuery();
			}
		}
		catch (Exception $e)
		{
			$this->ExecuteCreatingTableQuery();
		}
	}

	/**
	 * Выполняет запрос создания таблицы
	 */
	private function ExecuteCreatingTableQuery()
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
		$this->pdo->exec($query);
		$this->errorInfo = $this->pdo->errorInfo();
	}

	/**
	 * Сохраняет объект в базе данных
	 * @param string $objectKey - Если null, то ключ будет создан автоматически
	 * @param string $type
	 * @param object $object
	 * @param array $options
	 * @return string - Ключ, с которым соранен объект
	 */
	public function saveObject($objectKey, $type, $object, $options = array())
	{
		$textData = serialize($object);
		$params = array(
			':key' => $objectKey,
			':type' => $type,
			':data' => base64_encode($textData),
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
		$sqlStatement = $this->pdo->prepare($query);
		if ($sqlStatement->execute($params))
		{
			$sqlStatement->closeCursor();
			if (is_null($objectKey))
			{
				$objectKey = $this->pdo->lastInsertId();
				$params = array(
					':id' => $objectKey,
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
				$sqlStatement = $this->pdo->prepare($query);
				if (!$sqlStatement->execute($params))
				{
					$this->errorInfo = $this->pdo->errorInfo();
					$objectKey = null;
				}
				$sqlStatement->closeCursor();
			}
		}
		else
		{
			$this->errorInfo = $this->pdo->errorInfo();
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
	public function updateObject($objectKey, $object)
	{
		$textData = serialize($object);
		$params = array(
			':key' => $objectKey,
			':data' => base64_encode($textData),
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
		$sqlStatement = $this->pdo->prepare($query);
		$sqlStatement->execute($params);
		$this->errorInfo = $this->pdo->errorInfo();
		$sqlStatement->closeCursor();
	}

	/**
	 * Загружает объект их базе данных
	 * @param string $objectKey
	 * @return object
	 */
	public function restoreObject($objectKey)
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
		$sqlStatement = $this->pdo->prepare($query);
		$sqlStatement->execute($params);
		$this->errorInfo = $this->pdo->errorInfo();
		$queryResult = $sqlStatement->fetch(PDO::FETCH_ASSOC);
		if ($queryResult)
		{
			$data = base64_decode($queryResult['data']);
			$object = unserialize($data . ';');
			$this->errorInfo = error_get_last();
			$object = ($object === false ? null : $object);
		}
		$sqlStatement->closeCursor();
		return $object;
	}

}
