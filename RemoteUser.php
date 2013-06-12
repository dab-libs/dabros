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
 * Пользователь
 */
class RemoteUser
{

	/**
	 * Логин пользователя
	 * @var string
	 */
	private $login;

	/**
	 * Пароль пользователя
	 * @var string
	 */
	private $password;

	/**
	 * Роль пользователя
	 * @var string
	 */
	private $roles = array('guest');

	/**
	 * Создает объект
	 */
	public function __construct($login, $password)
	{
		$this->login = $login;
		$this->password = $password;
	}

	/**
	 * Возвращает логин
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * Устанавливает новый пароль
	 * @param string $oldPasswrd
	 * @param string $newPassword
	 */
	public function setPassword($oldPasswrd, $newPassword)
	{
		if ($this->_isPassword($oldPasswrd))
		{
			$this->password = $newPassword;
		}
	}

	/**
	 * Проверяет, что этот пользователь гость
	 * @return bool
	 */
	public function isGuest()
	{
		return (in_array('guest', $this->roles) && count($this->roles) == 1);
	}

	/**
	 * Устанавливает новый пароль
	 * @param string $password
	 */
	public function _isPassword($password)
	{
		return ($this->password == $password);
	}

	public function _getRoles()
	{
		return $this->roles;
	}

	public function _addRole($role)
	{
		if (!in_array($role, $this->roles)) $this->roles[] = $role;
	}

	public function _removeRole($role)
	{
		$pos = array_search($role, $this->roles);
		if ($pos !== false) unset($this->roles[$pos]);
	}

	public function _consts()
	{
		return array(
			'getLogin'
		);
	}

	public function _preloaded()
	{
		return array(
			'isGuest'
		);
	}

}
