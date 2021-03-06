<?php

namespace App\Extensions;

use App\Extensions\Installer\Model\InstallerModel;
use App\Helpers;
use Nette\Object;
use Nette\Security\IAuthorizator;

class Installer extends Object
{

	// <editor-fold defaultstate="collapsed" desc="constants & variables">

	const LOCK_FILE_CONTENT = '1';
	const LOCK_UNNAMED = '_UNNAMED_';
	const INSTALL_SUCCESS = TRUE;
	const INSTALL_LOCKED = FALSE;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $wwwDir;

	/** @var string */
	private $appDir;

	/** @var string */
	private $installDir;

	/** @var bool */
	private $lock = TRUE;

	/** @var bool */
	private $installDoctrine = FALSE;

	/** @var bool */
	private $installAdminer = FALSE;

	/** @var bool */
	private $installComposer = FALSE;

	/** @var array */
	private $initUsers = [];

	/** @var array */
	private $messages = [];

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="injects">

	/** @var InstallerModel @inject */
	public $model;

	/** @var IAuthorizator @inject */
	public $permissions;

	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="events">

	/** @var array */
	public $onSuccessInstall = [];

	/** @var array */
	public $onLockedInstall = [];

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="setters">

	/**
	 * Set nested pathes
	 * @param string $tempDir
	 * @param string $wwwDir
	 * @param string $appDir
	 * @return self
	 */
	public function setPathes($appDir, $wwwDir, $tempDir, $installDir)
	{
		$this->tempDir = $tempDir;
		$this->wwwDir = $wwwDir;
		$this->appDir = $appDir;
		$this->installDir = $installDir;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setLock($value)
	{
		$this->lock = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallDoctrine($value)
	{
		$this->installDoctrine = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallAdminer($value)
	{
		$this->installAdminer = (bool) $value;
		return $this;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function setInstallComposer($value)
	{
		$this->installComposer = (bool) $value;
		return $this;
	}

	/**
	 * @param array $value
	 * @return self
	 */
	public function setInitUsers(array $value)
	{
		$this->initUsers = $value;
		return $this;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="getters">

	private function getRoles()
	{
		return $this->permissions->getRoles();
	}

	private function getCompanyRoles()
	{
		return (new \App\Security\CompanyPermission)->getRoles();
	}

	private function getSkillLevels()
	{
		return [
			1 => 'N/A',
			2 => 'Basic',
			3 => 'Intermediate',
			4 => 'Advanced',
			5 => 'Expert',
		];
	}

	// </editor-fold>

	/**
	 * Install and return messages array
	 * @return array
	 */
	public function install()
	{
		$this->installComposer();
		$this->installAdminer();
		$this->installDb();
		return $this->messages;
	}

	// <editor-fold defaultstate="collapsed" desc="subinstallers">

	/**
	 * Run Composer
	 */
	private function installComposer()
	{
		if ($this->installComposer) {
			$name = $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$print = NULL;
				$this->model->installComposer($this->appDir, $print);
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS, $print];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Install Adminer needs
	 */
	private function installAdminer()
	{
		if ($this->installAdminer) {
			$name = $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$this->model->installAdminer($this->wwwDir);
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Install DB needs
	 * can create/update DB tables (install doctrine)
	 * set all nested thing (users, roles) to DB
	 */
	private function installDb()
	{
		$prefix = 'DB_';
		$this->installDoctrine($prefix);
		$this->installRoles($prefix);
		$this->installUsers($prefix);
		$this->installCompany($prefix);
		$this->installSkillLevels($prefix);
	}

	private function installDoctrine($lockPrefix = NULL)
	{
		if ($this->installDoctrine) {
			$name = $lockPrefix . $this->getLockName(__METHOD__);
			if ($this->lock($name)) {
				$this->model->installDoctrine();
				$this->onSuccessInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_SUCCESS];
			} else {
				$this->onLockedInstall($this, $name);
				$this->messages[$name] = [self::INSTALL_LOCKED];
			}
		}
	}

	/**
	 * Instal roles
	 * @param string $lockPrefix
	 */
	private function installRoles($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installRoles($this->getRoles());
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal users
	 * @param string $lockPrefix
	 */
	private function installUsers($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installUsers($this->initUsers);
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal company
	 * @param string $lockPrefix
	 */
	private function installCompany($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installCompanyRoles($this->getCompanyRoles());
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	/**
	 * Instal skill levels
	 * @param string $lockPrefix
	 */
	private function installSkillLevels($lockPrefix = NULL)
	{
		$name = $lockPrefix . $this->getLockName(__METHOD__);
		if ($this->lock($name)) {
			$this->model->installSkillLevels($this->getSkillLevels());
			$this->onSuccessInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_SUCCESS];
		} else {
			$this->onLockedInstall($this, $name);
			$this->messages[$name] = [self::INSTALL_LOCKED];
		}
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="lock functions">

	/**
	 * Lock file if locking is set AND lock is unused 
	 * Return TRUE if lock is FREE, return FALSE if lock is used
	 * @param string $name
	 * @return boolean
	 */
	private function lock($name)
	{
		if ($this->isLocked($name)) {
			return FALSE;
		} else {
			if ($this->lock) {
				file_put_contents($this->getLockFile($name), self::LOCK_FILE_CONTENT);
			}
			return TRUE;
		}
	}

	/**
	 * Check if lock is used
	 * @param string $name
	 * @return boolean
	 */
	private function isLocked($name)
	{
		return file_exists($this->getLockFile($name));
	}

	/**
	 * Return lock name from inserted method name
	 * @param string $method
	 * @return string
	 */
	private function getLockName($method)
	{
		$lockName = self::LOCK_UNNAMED;
		if (preg_match('~::install(.+)$~i', $method, $matches)) {
			$lockName = $matches[1];
		}
		return $lockName;
	}

	/**
	 * Return lock filename
	 * @param string $name
	 * @return string
	 */
	private function getLockFile($name)
	{
		Helpers::mkDir($this->installDir);
		return $this->installDir . '/' . $name . '.lock';
	}

	// </editor-fold>
}
