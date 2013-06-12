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
class UserSession
{

	private $config;

	/**
	 * Пользователь
	 * @var User
	 */
	private $user;

	public function __construct($config)
	{
		$this->config = $config;
		$this->logout();
	}

	public function getUser()
	{
		return $this->user;
	}

	public function login($user)
	{
		$this->user = $user;
	}

	public function logout()
	{
		$this->user = new RemoteObjectProxy(null, 'GuestUser');
	}

	public function getSessionFacade()
	{
		return dabros::getRemoteObjectManager()->getSessionSingleton($this->config['sessionFacadeClassName']);
	}

}
