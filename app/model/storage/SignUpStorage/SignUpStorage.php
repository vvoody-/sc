<?php

namespace App\Model\Storage;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use Exception;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Object;

/**
 * Description of RegistrationStorage
 *
 * @property Auth $auth
 * @property User $user
 * @property array $defaults
 * @property bool $verification
 * @property string $role
 * @property-read string $redirectRole
 */
class SignUpStorage extends Object
{

	/** @var Session */
	public $session;

	/** @var SessionSection */
	public $section;

	public function __construct(Session $session)
	{
		$this->section = $session->getSection('registration');
		$this->session = $session;
		
		$this->section->warnOnUndefined = TRUE;

		// Initialization
		$this->initSession();
	}

	// <editor-fold defaultstate="collapsed" desc="setters">

	/**
	 * @param User $user 
	 * @return self
	 */
	public function setUser(User $user)
	{
		$this->section->user = $user;
		return $this;
	}

	/**
	 * @param string $role 
	 * @return self
	 */
	public function setRole($role)
	{
		$this->section->role = $role;
		return $this;
	}

	/**
	 * Set if mail is verificate
	 * @param bool $bool
	 * @return self
	 */
	public function setVerification($bool)
	{
		$this->section->verification = $bool;
		return $this;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="getters">
	
	/**
	 * Get User from section
	 * @return User
	 */
	public function getUser()
	{
		return $this->section->user;
	}

	/**
	 * Get role from section
	 * @param type $forEntity if TRUE then retur role in Entity\Role format
	 * @return string
	 */
	public function getRole($forEntity = FALSE)
	{
		if (!isset($this->section->role)) {
			return NULL;
		}
		if ($forEntity) {
			switch ($this->section->role) {
				case Role::CANDIDATE:
				case Role::COMPANY:
					break;
				default:
					return Role::CANDIDATE;
			}
		}
		return $this->section->role;
	}

	/**
	 * Get translated role for redirect
	 * @return type
	 */
	public function getRedirectRole()
	{
		$role = $this->getRole();
			switch ($role) {
				case Role::COMPANY:
					$redirectRole = \App\FrontModule\Presenters\SignPresenter::ROLE_COMPANY;
					break;
				case Role::CANDIDATE:
					$redirectRole = \App\FrontModule\Presenters\SignPresenter::ROLE_CANDIDATE;
					break;
				default:
					$redirectRole = \App\FrontModule\Presenters\SignPresenter::ROLE_DEFAULT;
					break;
			}
		
		return $redirectRole;
	}

	/**
	 * Check if mail is verified
	 * @return boolean
	 */
	public function isVerified()
	{
		return $this->section->verification;
	}

	// </editor-fold>

	/**
	 * Init session
	 */
	public function wipe()
	{
		$this->initSession(TRUE);
	}
	
	/**
	 * Remove section
	 */
	public function remove()
	{
		$this->section->remove();
	}

	/**
	 * Set up all session properties to their default values.
	 * @param bool $force if FALSE then set only whem value is not set
	 * @return void
	 */
	private function initSession($force = FALSE)
	{
		$defaults = [
			'verification' => FALSE,
			'user' => new User(),
			'role' => NULL,
		];

		foreach ($defaults as $property => $value) {
			if ($force || !isset($this->section->{$property})) {
				$this->section->{$property} = $value;
			}
		}
	}

}

class SignUpStorageException extends Exception
{
	
}
