<?php

namespace App\Model\Facade;

use App\Model\Entity\Role;
use Kdyby\Doctrine\EntityDao;

class RoleFacade extends BaseFacade
{
	// <editor-fold defaultstate="collapsed" desc="constants & variables">

	/** @var EntityDao */
	private $roles;

	/** @var array */
	private $registrable = [Role::ROLE_CANDIDATE, Role::ROLE_COMPANY]; // ToDo: Move to configuration.

	// </editor-fold>

	protected function init()
	{
		$this->roles = $this->em->getDao(Role::getClassName());
	}

	// <editor-fold defaultstate="expanded" desc="create">

	/**
	 * Create role if is not exists.
	 * @param type $name
	 * @return Role|null
	 */
	public function create($name)
	{
		if ($this->isUnique($name)) {
			$entity = new Role;
			$entity->setName($name);
			return $this->roles->save($entity);
		}
		return NULL;
	}

	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="getters">

	/**
	 * Get all roles
	 * @return array
	 */
	public function getRoles()
	{
		return $this->roles->findPairs([], 'name', [], 'id');
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="finders">

	/**
	 * Find role by name.
	 * @param type $name
	 * @return Role
	 */
	public function findByName($name)
	{
		return $this->roles->findOneBy(['name' => $name]);
	}

	/**
	 * Find all lower roles
	 * TODO: TEST IT!!!
	 * @param array $roles expect ordered by priority (first is the lowest)
	 * @return array
	 */
	public function findLowerRoles(array $roles, $includeMax = FALSE)
	{
		$allRoles = $this->roles->findPairs('name', 'id'); // expect roles by priority (first is the lowest)
		$lowerRoles = [];
		$maxRole = Role::getMaxRole($roles);
		if (in_array($maxRole->name, $allRoles)) {
			foreach ($allRoles as $id => $dbRole) {
				if ($maxRole->name === $dbRole) {
					if ($includeMax) {
						$lowerRoles[$id] = $dbRole;
					}
					break;
				}
				$lowerRoles[$id] = $dbRole;
			}
		}
		return $lowerRoles;
	}

	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="checkers">

	/**
	 * Check if name is unique.
	 * @param type $name
	 * @return bool
	 */
	public function isUnique($name)
	{
		return $this->findByName($name) === NULL;
	}

	/**
	 * Check if role is allowed to register.
	 * @param string $roleName
	 */
	public function isRegistratable($roleName)
	{
		$role = $this->findByName($roleName);

		if ($role !== NULL && in_array($role->name, $this->registrable)) {
			return $role;
		}

		return FALSE;
	}

	// </editor-fold>
}
