<?php

namespace Test\Extensions\Installer;

use App\Extensions\Installer;
use App\Helpers;
use App\Model\Entity\User;
use App\Model\Facade\UserFacade;
use Nette\DI\Container;
use Nette\Security\IAuthorizator;
use Test\ParentTestCase;
use Tester\Assert;
use Tester\Helpers as Helpers2;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: Installer Testing
 *
 * @testCase
 * @phpVersion 5.4
 */
class InstallerTest extends ParentTestCase
{

	/** @var string */
	private $installDir;

	// <editor-fold defaultstate="collapsed" desc="injects">

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var IAuthorizator @inject */
	public $permissions;

	/** @var Installer @inject */
	public $installer;

	// </editor-fold>

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->installDir = $this->container->getParameters()['tempDir'] . 'install/';
	}

	// <editor-fold defaultstate="expanded" desc="tests">

	public function testInstaller()
	{
		// install empty values
		$messages1 = $this->installer->setPathes(NULL, NULL, NULL, NULL)
				->setLock(FALSE)
				->setInstallAdminer(FALSE)
				->setInstallComposer(FALSE)
				->setInstallDoctrine(FALSE)
				->setInitUsers([])
				->install();
		Assert::count(4, $messages1);
		Assert::same(['DB_Roles', 'DB_Users', 'DB_Company', 'DB_SkillLevels'], array_keys($messages1));
		Assert::same([[0 => TRUE], [0 => TRUE], [0 => TRUE], [0 => TRUE]], array_values($messages1));

		// install all (empty) with lock
		$messages2 = $this->installer->setPathes(NULL, NULL, NULL, $this->installDir)
				->setLock(TRUE)
				->setInstallAdminer(TRUE)
				->setInstallComposer(TRUE)
				->setInstallDoctrine(TRUE)
				->setInitUsers([])
				->install();
		Assert::count(7, $messages2);
		Assert::same(['DB_Roles', 'DB_Users', 'DB_Company', 'DB_SkillLevels', 'Composer', 'Adminer', 'DB_Doctrine'], array_keys($messages2));
		Assert::true($messages2['DB_Roles'][0]);
		Assert::true($messages2['DB_Users'][0]);
		Assert::true($messages2['DB_Company'][0]);
		Assert::true($messages2['DB_SkillLevels'][0]);
		Assert::true($messages2['Composer'][0]);
		Assert::true($messages2['Adminer'][0]);
		Assert::true($messages2['DB_Doctrine'][0]);

		// install after lock
		$messages3 = $this->installer->setPathes(NULL, NULL, NULL, $this->installDir)
				->setLock(TRUE)
				->setInstallAdminer(TRUE)
				->setInstallComposer(TRUE)
				->setInstallDoctrine(TRUE)
				->setInitUsers([])
				->install();
		Assert::count(7, $messages3);
		Assert::same(['DB_Roles', 'DB_Users', 'DB_Company', 'DB_SkillLevels', 'Composer', 'Adminer', 'DB_Doctrine'], array_keys($messages3));
		Assert::false($messages3['DB_Roles'][0]);
		Assert::false($messages3['DB_Users'][0]);
		Assert::false($messages3['DB_Company'][0]);
		Assert::false($messages3['DB_SkillLevels'][0]);
		Assert::false($messages3['Composer'][0]);
		Assert::false($messages3['Adminer'][0]);
		Assert::false($messages3['DB_Doctrine'][0]);

		// clear lock
		Helpers::delTree($this->installDir);

		$messages4 = $this->installer->setPathes(NULL, NULL, NULL, $this->installDir)
				->setLock(FALSE)
				->setInstallAdminer(FALSE)
				->setInstallComposer(FALSE)
				->setInstallDoctrine(FALSE)
				->setInitUsers([
					'user1' => ['password', 'guest'],
					'user2' => ['password', 'guest'],
				])
				->install();
		Assert::count(7, $messages4);
		Assert::same(['DB_Roles', 'DB_Users', 'DB_Company', 'DB_SkillLevels', 'Composer', 'Adminer', 'DB_Doctrine'], array_keys($messages4));
		Assert::true($messages4['DB_Roles'][0]);
		Assert::true($messages4['DB_Users'][0]);
		Assert::true($messages4['DB_Company'][0]);
		Assert::true($messages4['DB_SkillLevels'][0]);
		Assert::false($messages4['Composer'][0]);
		Assert::false($messages4['Adminer'][0]);
		Assert::false($messages4['DB_Doctrine'][0]);

		$user = $this->userFacade->findByMail('user');
		Assert::null($user);
		$user1 = $this->userFacade->findByMail('user1');
		Assert::same('user1', $user1->mail);
		Assert::same([1 => 'guest'], $user1->roles);
		$userDao = $this->em->getDao(User::getClassName());
		Assert::count(2, $userDao->findAll());
	}

	// </editor-fold>

	public function setUp()
	{
		$this->updateSchema();
		Helpers2::purge($this->installDir);
	}

	public function tearDown()
	{
		$this->dropSchema();
		Helpers::delTree($this->installDir);
	}

}

$test = new InstallerTest($container);
$test->run();
