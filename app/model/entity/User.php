<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 *
 * @property string $email
 * @property string $firstname
 * @property string $surname
 * @property \Doctrine\Common\Collections\ArrayCollection $auths
 * @property \Doctrine\ORM\PersistentCollection $roles
 * @property string $recoveryToken
 * @property \DateTime $recoveryExpiration
 *
 * @method \Doctrine\ORM\PersistentCollection getRoles()
 */
class User extends \Kdyby\Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier;

	/**
	 * @ORM\Column(type="string", nullable=false)
	 */
	protected $email;

	/**
	 * @ORM\OneToMany(targetEntity="Auth", mappedBy="user", cascade={"persist","remove"})
	 * */
	protected $auths;

	/**
	 * @ORM\ManyToMany(targetEntity="Role", fetch="EAGER")
	 */
	protected $roles;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $firstname;
    
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $surname;
	
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    protected $name;
	
	/**
	 * @ORM\Column(type="string", length=256, nullable=true)
	 */
	protected $recoveryToken;
	
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
	protected $recoveryExpiration;
	
	
	public function __construct()
	{
		$this->auths = new \Doctrine\Common\Collections\ArrayCollection;
		$this->roles = new \Doctrine\Common\Collections\ArrayCollection;
	}

	public function toArray()
	{
		return [
			'id' => $this->id,
			'email' => $this->email,
			'role' => $this->roles->toArray()
		];
	}

	/**
	 *
	 * @return int
	 */
	public function getUsername()
	{
		return $this->email;
	}

	/**
	 *
	 * @param Role|array $element
	 * @param bool $clear
	 * @return self
	 */
	public function addRole($element, $clear = FALSE)
	{
		if ($clear) {
			$this->clearRoles();
		}

		if (is_array($element)) {
			foreach ($element as $item) {
				$this->roles->add($item);
			}
		} else {
			if (!$this->roles->contains($element)) {
				$this->roles->add($element);
			}
		}
		return $this;
	}

	/**
	 *
	 * @param Role $element
	 * @return self
	 */
	public function removeRole(Role $element)
	{
		if ($this->roles->contains($element)) {
			$this->roles->removeElement($element);
		}
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	public function clearRoles()
	{
		$this->roles->clear();
		return $this;
	}

	/**
	 *
	 * @return int
	 */
	public function getRolesCount()
	{
		return $this->roles->count();
	}

	/**
	 *
	 * @param bool $keysOnly if TRUE than return only keys
	 * @return array
	 */
	public function getRolesPairs()
	{
		$array = [];
		foreach ($this->roles as $role) {
			$array[$role->id] = $role->name;
		}
		return $array;
	}
	
	public function getRolesKeys()
	{
		$array = [];
		foreach ($this->roles as $role) {
			$array[] = $role->id;
		}
		return $array;
	}

	public function addAuth($auth)
	{
		$this->auths->add($auth);
		$auth->user = $this;
	}

	public function __toString()
	{
		return $this->email;
	}
	
	/**
	 * Set or reset recovery code and expiration DateTime.
	 * @param string $code
	 * @param \DateTime|string $expiration
	 * @return User
	 */
	public function setRecovery($code = NULL, $expiration = NULL)
	{
		if (func_num_args() === 0) {
			$this->recoveryToken = NULL;
			$this->recoveryExpiration = NULL;
		} else {
			$this->recoveryToken = $code;
			
			if ($expiration instanceof \DateTime) {
				$this->recoveryExpiration = $expiration;
			} else {
				$this->recoveryExpiration = (new \DateTime())->add(\DateInterval::createFromDateString($expiration));
			}
		}
		
		return $this;
	}

}
