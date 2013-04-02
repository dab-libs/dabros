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
	private $tableName = 'dabros_storage';

	/**
	 * Создает объект
	 * @param mixed $connection - объект PDO или массив со следующими полями:
	 * <ul>
	 * <li>connectionString - Имя источника данных или DSN, содержащее информацию, необходимую для подключения к базе данных
	 * <li>username - Имя пользователя для строки DSN. Необязательно. По умолчанию: ""
	 * <li>password - Пароль для строки DSN. Необязательно. По умолчанию: ""
	 * <li>options - Массив специфичных для драйвера настроек подключения ключ=>значение. Необязательно. По умолчанию: array()
	 * <li>table - Название таблицы. Необязательно. По умолчанию: "dabros_storage"
	 * </ul>
	 * @param string $tableName - имя таблицы для хранания удаленно управляемых объектов
	 */
	public function __construct($connection, $tableName = null)
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
		if (!is_null($tableName))
		{
			$this->tableName = $tableName;
		}
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
SHOW TABLES LIKE '{$this->tableName}'
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
CREATE TABLE `{$this->tableName}` (
	`objectId` int(11) NOT NULL AUTO_INCREMENT,
	`data` blob,
	`textData` text,
	`created` datetime DEFAULT NULL,
	`modified` datetime DEFAULT NULL,
	PRIMARY KEY (`objectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
QUERY;
		$this->pdo->exec($query);
		$this->errorInfo = $this->pdo->errorInfo();
	}

	/**
	 * Сохраняет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 * @return int - Идентификатор объекта
	 */
	public function saveObject($object, $objectId = -1)
	{
		$textData = serialize($object);
		$params = array(
			':data' => base64_encode($textData),
			':textData' => $textData,
		);
		if ($objectId == -1)
		{
			$query = <<<QUERY
INSERT INTO `{$this->tableName}` (
	`data`,
	`textData`,
	`created`,
	`modified`
) VALUES (
	:data,
	:textData,
	NOW(),
	NOW()
)
QUERY;
		}
		else
		{
			$params['objectId'] = $objectId;
			$query = <<<QUERY
INSERT INTO `{$this->tableName}` (
	`objectId`,
	`data`,
	`textData`,
	`created`,
	`modified`
) VALUES (
	:objectId,
	:data,
	:textData,
	NOW(),
	NOW()
)
QUERY;
		}
		$sqlStatement = $this->pdo->prepare($query);
		$sqlStatement->execute($params);
		$this->errorInfo = $this->pdo->errorInfo();
		return $this->pdo->lastInsertId();
	}

	/**
	 * Обновляет объект в базе данных
	 * @param object $object
	 * @param int $objectId
	 */
	public function updateObject($object, $objectId)
	{
		$textData = serialize($object);
		$params = array(
			':data' => base64_encode($textData),
			':textData' => $textData,
			':objectId' => $objectId,
		);
		$query = <<<QUERY
UPDATE
	`{$this->tableName}`
SET
	`data` = :data,
	`textData` = :textData,
	`modified` = NOW()
WHERE
	`objectId` = :objectId
)
QUERY;
		$sqlStatement = $this->pdo->prepare($query);
		$sqlStatement->execute($params);
		$this->errorInfo = $this->pdo->errorInfo();
	}

	/**
	 * Загружает объект их базе данных
	 * @param int $objectId
	 * @return object
	 */
	public function restoreObject($objectId)
	{
		$object = null;
		$params = array(
			':objectId' => $objectId,
		);
		$query = <<<QUERY
SELECT *
FROM
	`{$this->tableName}`
WHERE
	`objectId` = :objectId
QUERY;
		$sqlStatement = $this->pdo->prepare($query);
		$sqlStatement->execute($params);
		$this->errorInfo = $this->pdo->errorInfo();
		$queryResult = $sqlStatement->fetch(PDO::FETCH_ASSOC);
		if ($queryResult)
		{
			$data = base64_decode($queryResult['data']);
			$object = unserialize($data . ';');
			$error = error_get_last();
			$object = ($object === false ? null : $object);
		}
		$sqlStatement->closeCursor();
		return $object;
	}
}
