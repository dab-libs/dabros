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
require_once 'RemoteObjectStorageInterface.php';
require_once 'RemoteObjectProxy.php';
require_once 'RemoteObjectManager.php';
require_once 'RemoteObjectStorage.php';

/**
 * Description of dabros
 */
class dabros
{

	protected static $instance;

	public static function initialize($config)
	{
		if (!is_null(self::$instance))
		{
			throw new RemoteObjectException('dabros is already initialized');
		}
		if ($config['RemoteObjectManager'] instanceof RemoteObjectManager)
		{
			self::$instance = new dabros($config['RemoteObjectManager']);
		}
		else
		{
			self::$instance = new dabros(self::createComponent($config['RemoteObjectManager'], 'RemoteObjectManager'));
		}
	}

	/**
	 * Создает объект
	 * @param type $remoteObjectManager
	 */
	private function __construct($remoteObjectManager)
	{
		$this->remoteObjectManager = $remoteObjectManager;
	}

	private function __clone()
	{

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
	protected $remoteObjectManager;

	/**
	 * Возвращает экземпляр RemoteObjectManager
	 * @return RemoteObjectManager
	 */
	public static function getRemoteObjectManager()
	{
		return self::getInstance()->remoteObjectManager;
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
	 * @param type $javaScriptPublicPath
	 */
	public static function printJavaScriptTags($javaScriptPublicPath)
	{
		$javaScriptList = self::copyJavaScriptToPublicPath($javaScriptPublicPath);
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
			$publicJavaScriptList[] = preg_replace("/\/+/", '/', str_replace("\\", '/', DIRECTORY_SEPARATOR . $javaScriptPublicPath . DIRECTORY_SEPARATOR . $javaScriptFileName));
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


}
