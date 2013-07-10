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
require_once 'RemoteStorageInterface.php';
require_once 'RemoteObjectProxy.php';
require_once 'RemoteObjectManager.php';
require_once 'RemoteCallManager.php';
require_once 'RemoteUser.php';
require_once 'RemoteGuest.php';
require_once 'RemoteUserSession.php';

/**
 * Description of dabros
 */
class dabros
{

	/**
	 * @var dabros
	 */
	protected static $instance;
	private $config;

	public static function initialize($config)
	{
		if (!is_null(self::$instance))
		{
			throw new RemoteObjectException('dabros is already initialized');
		}
		self::$instance = new dabros($config);
		self::$instance->getRemoteUserSession();
	}

	/**
	 * Возвращает экземпляр
	 * @return dabros
	 * @throws RemoteObjectException
	 */
	protected static function getInstance()
	{
		if (is_null(self::$instance))
		{
			throw new RemoteObjectException('dabros is not initialized');
		}
		return self::$instance;
	}

	/**
	 * @var RemoteObjectManager
	 */
	protected $remoteObjectManager = null;

	/**
	 * Возвращает экземпляр RemoteObjectManager
	 * @return RemoteObjectManager
	 */
	public static function getRemoteObjectManager()
	{
		$_this = self::getInstance();
		if (is_null($_this->remoteObjectManager))
		{
			$_this->remoteObjectManager = self::createComponent($_this->config['RemoteObjectManager'], 'RemoteObjectManager');
		}
		return $_this->remoteObjectManager;
	}

	/**
	 * @var RemoteCallManager
	 */
	protected $remoteCallManager = null;

	/**
	 * Возвращает экземпляр RemoteCallManager
	 * @return RemoteCallManager
	 */
	public static function getRemoteCallManager()
	{
		$_this = self::getInstance();
		if (is_null($_this->remoteCallManager))
		{
			$_this->remoteCallManager = self::createComponent($_this->config['RemoteCallManager'], 'RemoteCallManager');
		}
		return $_this->remoteCallManager;
	}

	/**
	 * Возвращает экземпляр UserSession
	 * @return UserSession
	 */
	public static function getRemoteUserSession()
	{
		$_this = self::getInstance();
		if (!isset($_SESSION))
		{
			try
			{
				session_start();
			}
			catch (Exception $exc)
			{

			}
		}
		if (!isset($_SESSION['RemoteUserSession']))
		{
			$_SESSION['RemoteUserSession'] = self::createComponent($_this->config['RemoteUserSession'], 'RemoteUserSession');
		}
		return $_SESSION['RemoteUserSession'];
	}

	/**
	 * Возвращает значение ключа из раздела настроек 'params'
	 * @param string $paramKey
	 * @return mixed
	 */
	public static function getParam($paramKey)
	{
		$_this = self::getInstance();
		return $_this->config['params'][$paramKey];
	}

	/**
	 * Возвращает массив путей к JavaScript-файлам библиотеки Dabros
	 * @return array
	 */
	public static function getJavaScriptList()
	{
		$jsDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
		return array(
			$jsDir . 'jquery.min.js',
			$jsDir . 'jquery.json.min.js',
			$jsDir . 'dabros.js',
		);
	}

	/**
	 * Вставляет на страницу теги для подключения JavaScript-файлов библиотеки Dabros
	 */
	public static function printJavaScriptTags()
	{
		$dabrosUrl = self::getInstance()->config['dabrosUrl'];
		$sessionFacade = self::getRemoteCallManager()->handleRequest((object) array(
					'id' => 0,
					'objectId' => 0,
					'method' => 'getFacade',
				));
		$sessionFacade = json_encode($sessionFacade);
		echo <<<SCRIPT
<script>
	dabrosConfig = {
		dabrosUrl: '{$dabrosUrl}',
		sessionFacade: {$sessionFacade}
	};
</script>
SCRIPT;
		$javaScriptList = self::copyJavaScriptToPublicPath(self::$instance->config['javaScrptPath']);
		foreach ($javaScriptList as $javaScript)
		{
			echo '<script src="' . $javaScript . '"></script>' . "\n";
		}
	}

	/**
	 * Копирует JavaScript-файлы библиотеки Dabros в публичную папку
	 * @param string $javaScriptPublicPath
	 * @return array
	 */
	protected static function copyJavaScriptToPublicPath($javaScriptPublicPath)
	{
		$publicJavaScriptList = array();
		$javaScriptList = self::getJavaScriptList();
		foreach ($javaScriptList as $javaScript)
		{
			$javaScriptFileName = pathinfo($javaScript);
			$javaScriptFileName = $javaScriptFileName['basename'];
			$documentJavaScript = DIRECTORY_SEPARATOR . $javaScriptPublicPath . DIRECTORY_SEPARATOR . $javaScriptFileName;
			$documentJavaScript = $_SERVER['DOCUMENT_ROOT'] . preg_replace("/\/+/", '/', str_replace("\\", '/', $documentJavaScript));
			if (!file_exists($documentJavaScript) || filemtime($documentJavaScript) < filemtime($javaScript))
			{
				copy($javaScript, $documentJavaScript);
			}
			$publicJavaScriptList[] = preg_replace("/\/+/", '/',
					str_replace("\\", '/', DIRECTORY_SEPARATOR . $javaScriptPublicPath . DIRECTORY_SEPARATOR . $javaScriptFileName));
		}
		return $publicJavaScriptList;
	}

	/**
	 *
	 * @param type $config
	 * @param type $class
	 * @return mixed
	 */
	public static function createComponent($config, $class)
	{
		if (isset($config['class']))
		{
			$class = $config['class'];
		}
		return new $class($config);
	}

	/**
	 * Загружает заданный класс
	 * @param string $className
	 */
	public static function loadClass($className)
	{
		require self::$instance->config['phpClassPath'] . DIRECTORY_SEPARATOR . $className . '.php';
	}

	/**
	 * Создает объект
	 * @param type $config
	 */
	private function __construct($config)
	{
		$this->config = $config;
		if (isset($this->config['phpClassPath']))
		{
			spl_autoload_register(array('dabros', 'loadClass'));
		}
	}

	private function __clone()
	{

	}

}
