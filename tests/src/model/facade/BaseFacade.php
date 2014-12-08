<?php

namespace Test\Model\Facade;

use App\Extensions\Settings\Model\Storage\DefaultSettingsStorage;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use Test\ParentTestCase;

/**
 * Parent of facades' tests
 */
abstract class BaseFacade extends ParentTestCase
{

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var DefaultSettingsStorage @inject */
	public $defaultSettings;

	public function setUp()
	{
		$this->updateSchema();
	}

	public function tearDown()
	{
		$this->dropSchema();
		$this->em->clear();
	}

}
