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
 * Гость
 */
class RemoteGuest extends RemoteUser
{

	public function __construct()
	{
		parent::__construct('_guest_', '');
	}

	/**
	 * Устанавливает новый пароль
	 * @param string $oldPasswrd
	 * @param string $newPassword
	 */
	public function setPassword($oldPasswrd, $newPassword)
	{
		return false;
	}

	/**
	 * Устанавливает новый пароль
	 * @param string $passwrd
	 */
	public function _isPassword($password)
	{
		return false;
	}

	public function _getRoles()
	{
		return array('guest');
	}

	public function _addRole($role)
	{
	}

	public function _removeRole($role)
	{
	}

}
