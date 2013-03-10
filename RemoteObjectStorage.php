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
 * Хранилаще удаленно используемых объектов
 */
class RemoteObjectStorage extends CApplicationComponent implements RemoteObjectStorageInterface
{

	public $connectionID = 'db';
	public $autoCreate = true;
	public $tableName = 'roStorage';

	public function init()
	{
		parent::init();

		if ($this->autoCreate)
		{
			try
			{
				$this->getDbConnection()->createCommand()->delete($this->tableName, '0=1');
			}
			catch (Exception $e)
			{
				$this->createTable();
			}
		}
	}

	public function __destruct()
	{
		foreach ($this->objectCache as $objectId => $object)
		{
			$this->updateObject($object, $objectId);
		}
	}

	protected $objectCache = array();

	/**
	 *
	 * @param string $className
	 * @param int $objectId
	 * @return RemoteObjectProxy
	 */
	public function createObject($className, $objectId = -1)
	{
		$object = new $className();
		$objectId = $this->saveObject($object, $objectId);
		$this->objectCache[$objectId] = $object;
		return $objectId;
	}

	/**
	 *
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getObject($objectId)
	{
		$object = null;
		if (isset($this->objectCache[$objectId]))
		{
			$object = $this->objectCache[$objectId];
		}
		else
		{
			$object = $this->restoreObject($objectId);
			if (!is_null($object))
			{
				$this->objectCache[$objectId] = $object;
			}
		}
		return $object;
	}

	protected $indepedentObjectCache = array();

	/**
	 *
	 * @param string $className
	 * @param integer $objectId
	 * @return RemoteObjectProxy
	 */
	public function getIndepedentObject($className, $objectId)
	{
		if (!isset($this->indepedentObjectCache[$className])) $this->indepedentObjectCache[$className] = array();
		if (!isset($this->indepedentObjectCache[$className][$objectId]))
		{
			$object = new $className($objectId);
			$this->indepedentObjectCache[$className][$objectId] = $object;
		}
		return $this->indepedentObjectCache[$className][$objectId];
	}

	protected $_db = null;

	/**
	 * @return CDbConnection the DB connection instance
	 * @throws CException if {@link connectionID} does not point to a valid application component.
	 */
	protected function getDbConnection()
	{
		if ($this->_db !== null)
		{
			return $this->_db;
		}
		elseif (($id = $this->connectionID) !== null)
		{
			if (($this->_db = Yii::app()->getComponent($id)) instanceof CDbConnection)
			{
				return $this->_db;
			}
			else
			{
				throw new CException(Yii::t('yii',
								'CDbHttpSession.connectionID "{id}" is invalid. Please make sure it refers to the ID of a CDbConnection application component.',
								array('{id}' => $id)));
			}
		}
		else
		{
			$dbFile = Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'property-' . Yii::getVersion() . '.db';
			return $this->_db = new CDbConnection('sqlite:' . $dbFile);
		}
	}

	/**
	 * Creates the DB table for storing log messages.
	 * @param CDbConnection $this->_db the database connection
	 * @param string $this->tableName the name of the table to be created
	 */
	protected function createTable()
	{
		$this->getDbConnection()->createCommand()->createTable($this->tableName,
				array(
			'objectId' => 'pk',
			'data' => 'blob',
			'textData' => 'text',
			'created' => 'datetime',
			'modified' => 'datetime',
		));
	}

	public function saveObject($object, $objectId = -1)
	{
		$textData = serialize($object);
		$columns = array(
			'data' => base64_encode($textData),
			'textData' => $textData,
			'created' => new CDbExpression('NOW()'),
			'modified' => new CDbExpression('NOW()'),
		);
		if ($objectId != -1)
		{
			$columns['objectId'] = $objectId;
		}
		$this->getDbConnection()->createCommand()->insert($this->tableName, $columns);
		return $this->getDbConnection()->lastInsertID;
	}

	public function updateObject($object, $objectId)
	{
		$textData = serialize($object);
		$columns = array(
			'data' => base64_encode($textData),
			'textData' => $textData,
			'modified' => new CDbExpression('NOW()'),
		);
		$this->getDbConnection()->createCommand()->update($this->tableName, $columns, '`objectId` = :objectId',
				array(
			':objectId' => $objectId,
		));
	}

	public function restoreObject($objectId)
	{
		$queryResult = $this->getDbConnection()->createCommand()
				->select('*')
				->from($this->tableName)
				->where('`objectId` = :objectId', array(':objectId' => $objectId))
				->queryRow();
		$object = null;
		if ($queryResult)
		{
			$data = base64_decode($queryResult['data']);
			$object = unserialize($data . ';');
			$error = error_get_last();
			$object = ($object === false ? null : $object);
		}
		return $object;
	}

}

function unserializeCallback($className)
{
	$r = Yii::autoload($className);
	return $r;
}