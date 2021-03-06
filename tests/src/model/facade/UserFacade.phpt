<?php

namespace Test\Model\Facade;

use App\Model\Entity\Facebook;
use App\Model\Entity\PageConfigSettings;
use App\Model\Entity\PageDesignSettings;
use App\Model\Entity\Registration;
use App\Model\Entity\Role;
use App\Model\Entity\Twitter;
use App\Model\Entity\User;
use Kdyby\Doctrine\EntityDao;
use Nette\DI\Container;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Tester\Assert;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * TEST: UserFacade
 *
 * @testCase
 * @phpVersion 5.4
 */
class UserFacadeTest extends BaseFacade
{

	const MAIL = 'user.mail@domain.com';
	const PASSWORD = 'password123456';
	const EXPIRED_TOKEN = 'expiredToken';
	const VALID_TOKEN = 'validToken';
	const TWITTER_ID = 'tw123456789';
	const FACEBOOK_ID = 'fb123456789';

	/** @var EntityDao */
	private $userDao;

	/** @var EntityDao */
	private $roleDao;

	/** @var EntityDao */
	private $registrationDao;

	/** @var EntityDao */
	private $facebookDao;

	/** @var EntityDao */
	private $twitterDao;

	/** @var EntityDao */
	private $pageConfigSettingsDao;

	/** @var EntityDao */
	private $pageDesignSettingsDao;

	/** @var User */
	private $user;

	public function __construct(Container $container)
	{
		parent::__construct($container);
		$this->userDao = $this->em->getDao(User::getClassName());
		$this->roleDao = $this->em->getDao(Role::getClassName());
		$this->registrationDao = $this->em->getDao(Registration::getClassName());
		$this->facebookDao = $this->em->getDao(Facebook::getClassName());
		$this->twitterDao = $this->em->getDao(Twitter::getClassName());
		$this->pageConfigSettingsDao = $this->em->getDao(PageConfigSettings::getClassName());
		$this->pageDesignSettingsDao = $this->em->getDao(PageDesignSettings::getClassName());
	}

	public function setUp()
	{
		parent::setUp();
		$role = $this->roleFacade->create(Role::CANDIDATE);
		$this->user = $this->userFacade->create(self::MAIL, 'password', $role);
		$this->user->facebook = new Facebook(self::FACEBOOK_ID);
		$this->user->twitter = new Twitter(self::TWITTER_ID);
		$this->user->pageConfigSettings = new PageConfigSettings;
		$this->user->pageDesignSettings = new PageDesignSettings;

		$this->userDao->save($this->user);
		$this->userDao->clear();
		$this->roleDao->clear();
	}

	public function testCreate()
	{
		$mail = 'second.user@domain.com';
		$password = 'password654321';
		$role = $this->roleFacade->findByName(Role::CANDIDATE);

		Assert::null($this->userFacade->create(self::MAIL, self::PASSWORD, $role)); // Create user with existing e-mail

		$user = $this->userFacade->create($mail, $password, $role);
		Assert::type(User::getClassName(), $user);
		Assert::same($user->mail, $mail);
		Assert::true(Passwords::verify($password, $user->hash));

		Assert::true(in_array(Role::CANDIDATE, $user->getRoles()));

		Assert::same(1, $this->user->id);
		Assert::same(2, $user->id);

		$this->userDao->delete($user);
		Assert::null($user->id);
	}

	public function testDelete()
	{
		Assert::count(1, $this->roleDao->findAll());
		Assert::count(1, $this->userDao->findAll());
		Assert::count(1, $this->facebookDao->findAll());
		Assert::count(1, $this->twitterDao->findAll());
		Assert::count(1, $this->pageConfigSettingsDao->findAll());
		Assert::count(1, $this->pageDesignSettingsDao->findAll());

		$this->userFacade->deleteById($this->user->id);
		$this->userDao->clear();

		Assert::count(1, $this->roleDao->findAll());
		Assert::count(0, $this->userDao->findAll());
		Assert::count(0, $this->facebookDao->findAll());
		Assert::count(0, $this->twitterDao->findAll());
		Assert::count(0, $this->pageConfigSettingsDao->findAll());
		Assert::count(0, $this->pageDesignSettingsDao->findAll());
	}

	public function testFindBy()
	{
		$user1 = $this->userFacade->findByMail(self::MAIL);
		Assert::type(User::getClassName(), $user1);
		Assert::same(self::MAIL, $user1->mail);

		$user2 = $this->userFacade->findByFacebookId(self::FACEBOOK_ID);
		Assert::type(User::getClassName(), $user2);
		Assert::same(self::FACEBOOK_ID, $user2->facebook->id);

		$user3 = $this->userFacade->findByTwitterId(self::TWITTER_ID);
		Assert::type(User::getClassName(), $user3);
		Assert::same(self::TWITTER_ID, $user3->twitter->id);
	}

	public function testRecoveryToken()
	{
		// Expired token
		/* @var $user1 User */
		$user1 = $this->userDao->find($this->user->id);
		$user1->setRecovery(self::EXPIRED_TOKEN, 'now - 1 day');
		$this->userDao->save($user1);

		$this->userDao->clear();
		Assert::null($this->userFacade->findByRecoveryToken(self::EXPIRED_TOKEN));

		/* @var $user2 User */
		$user2 = $this->userDao->find($this->user->id);
		Assert::null($user2->recoveryExpiration);
		Assert::null($user2->recoveryToken);

		// Valid token
		$user2->setRecovery(self::VALID_TOKEN, 'now + 1 day');
		$this->userDao->save($user2);
		$this->userDao->clear();

		/* @var $user3 User */
		$user3 = $this->userFacade->findByRecoveryToken(self::VALID_TOKEN);
		Assert::type(User::getClassName(), $user3);
		Assert::same(self::VALID_TOKEN, $user3->recoveryToken);
	}

	public function testSetRecovery()
	{
		/* @var $user1 User */
		$user1 = $this->userDao->find($this->user->id);
		$this->userFacade->setRecovery($user1);
		$this->userDao->save($user1);
		$this->userDao->clear();

		/* @var $user2 User */
		$user2 = $this->userDao->find($this->user->id);
		Assert::same($user1->recoveryToken, $user2->recoveryToken);
		Assert::equal($user1->recoveryExpiration, $user2->recoveryExpiration);
	}

	public function testIsUnique()
	{
		Assert::false($this->userFacade->isUnique(self::MAIL));
		Assert::true($this->userFacade->isUnique('not@unique.com'));
	}

	public function testRegistration()
	{
		$user1 = $this->userDao->find($this->user->id);
		$user1->requiredRole = $this->roleDao->find(1);
		Assert::count(0, $this->registrationDao->findAll());
		$this->userFacade->createRegistration($user1);
		Assert::count(1, $this->registrationDao->findAll());

		/* @var $registration Registration */
		$registration = $this->registrationDao->find(1);
		Assert::same($user1->mail, $registration->mail);
		Assert::same($user1->hash, $registration->hash);
		Assert::same($user1->requiredRole->id, $registration->role->id);
		Assert::same($user1->facebook->id, $registration->facebookId);
		Assert::same($user1->twitter->id, $registration->twitterId);

		// clear previous with same mail
		$this->userFacade->createRegistration($user1);
		$this->registrationDao->clear();
		Assert::count(1, $this->registrationDao->findAll());

		$user1->mail = 'another.user@domain.com';
		$this->userFacade->createRegistration($user1);
		$this->registrationDao->clear();
		Assert::count(2, $this->registrationDao->findAll());
	}

	public function testCreateFromRegistration()
	{
		$user = new User;
		$user->setMail('new@user.com')
				->setPassword('password')
				->setFacebook(new Facebook('facebookID'))
				->setTwitter(new Twitter('twitterID'))
				->setRequiredRole($this->roleDao->find(1));
		$registration = $this->userFacade->createRegistration($user);
		$this->registrationDao->clear();
		Assert::count(1, $this->registrationDao->findAll());

		$this->roleFacade->create(Role::SIGNED);
		$initRole = $this->roleFacade->findByName(Role::SIGNED);
		$findedRegistration = $this->registrationDao->find($registration->id);
		$this->userFacade->createFromRegistration($findedRegistration, $initRole);
		$this->userDao->clear();
		Assert::count(2, $this->userDao->findAll());

		$newUser = $this->userFacade->findByMail($user->mail);
		Assert::type(User::getClassName(), $newUser);
		Assert::same($user->mail, $newUser->mail);
		Assert::same($user->hash, $newUser->hash);
		Assert::same($initRole->id, $newUser->getMaxRole()->id);
		Assert::same($user->requiredRole->id, $newUser->requiredRole->id);
		Assert::same($user->facebook->id, $newUser->facebook->id);
		Assert::same($user->twitter->id, $newUser->twitter->id);
	}

	public function testVerificationToken()
	{
		$this->roleFacade->create(Role::COMPANY);
		$this->roleDao->clear();
		$role = $this->roleFacade->findByName(Role::COMPANY);

		$registration1 = new Registration;
		$registration1->mail = 'user1@mail.com';
		$registration1->role = $role;
		$registration1->verificationToken = 'verificationToken1';
		$registration1->verificationExpiration = DateTime::from('now +1 hour');
		$this->registrationDao->save($registration1);
		$this->registrationDao->clear();
		Assert::count(1, $this->registrationDao->findAll());

		$findedRegistration1 = $this->userFacade->findByVerificationToken($registration1->verificationToken);
		Assert::type(Registration::getClassName(), $findedRegistration1);
		Assert::same($registration1->mail, $findedRegistration1->mail);

		$registration2 = new Registration;
		$registration2->mail = 'user2@mail.com';
		$registration2->role = $role;
		$registration2->verificationToken = 'verificationToken2';
		$registration2->verificationExpiration = DateTime::from('now -1 hour');
		$this->registrationDao->save($registration2);
		$this->registrationDao->clear();
		Assert::count(2, $this->registrationDao->findAll());

		$findedRegistration2 = $this->userFacade->findByVerificationToken($registration2->verificationToken);
		Assert::null($findedRegistration2);
		Assert::count(1, $this->registrationDao->findAll());

		Assert::null($this->userFacade->findByVerificationToken('unknown token'));
	}

	public function testAddRole()
	{
		$roleA = $this->roleFacade->create(Role::COMPANY);
		$roleB = $this->roleFacade->create(Role::ADMIN);
		$this->userFacade->addRole($this->user, Role::COMPANY);
		Assert::count(2, $this->user->roles);
		$this->user->removeRole($roleA);
		$this->userFacade->addRole($this->user, [Role::COMPANY, Role::ADMIN]);
		Assert::count(3, $this->user->roles);
	}

	public function testAppendSettings()
	{
		$newConfigSettings = new PageConfigSettings;
		$newDesignSettings = new PageDesignSettings;
		$newDesignSettings->color = 'red';
		$this->userFacade->appendSettings($this->user->id, $newConfigSettings, $newDesignSettings);

		$user1 = $this->userDao->find($this->user->id);
		/* @var $user1 User */
		Assert::null($user1->pageConfigSettings->language);
		Assert::same('red', $user1->pageDesignSettings->color);
		Assert::null($user1->pageDesignSettings->footerFixed);

		$rewriteConfigSettings = new PageConfigSettings;
		$rewriteConfigSettings->language = 'de';
		$rewriteDesignSettings = new PageDesignSettings;
		$rewriteDesignSettings->color = 'blue';
		$rewriteDesignSettings->footerFixed = TRUE;
		$this->userFacade->appendSettings($this->user->id, $rewriteConfigSettings, $rewriteDesignSettings);

		$user2 = $this->userDao->find($this->user->id);
		/* @var $user2 User */
		Assert::same('de', $user2->pageConfigSettings->language);
		Assert::same('red', $user2->pageDesignSettings->color);
		Assert::same(TRUE, $user2->pageDesignSettings->footerFixed);
	}

}

$test = new UserFacadeTest($container);
$test->run();
