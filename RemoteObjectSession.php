<?php
/**
 * Dabros version 0.1.0
 * RPC Library for PHP & JavaScript
 *
 * @author  Dmitry Bystrov <uncle.demian@gmail.com>, 2013
 * @source  https://github.com/dab-libs/dabros
 * @date    2013-05-26
 * @license Lesser GPL licenses (http://www.gnu.org/copyleft/lesser.html)
 */

/**
 * Сессия
 */
class RemoteObjectSession
{

	/**
	 * Возвращает экземпляр
	 * @return RemoteObjectSession
	 */
	public static function getInstance()
	{
		if (!isset($_SESSION))
		{
			session_start();
		}
		if (!isset($_SESSION['RemoteObjectSession']))
		{
			$_SESSION['RemoteObjectSession'] = new RemoteObjectSession();
		}
		return $_SESSION['RemoteObjectSession'];
	}

	/**
	 * Роль пользователя
	 * @var string
	 */
	private $userRole = 'guest';

	public function getUserRole()
	{
		return $this->userRole;
	}

	public function setUserRole($userRole)
	{
		$this->userRole = $userRole;
	}

	/**
	 * Создает объект
	 */
	protected function __construct()
	{

	}

	private function __clone()
	{

	}

}
