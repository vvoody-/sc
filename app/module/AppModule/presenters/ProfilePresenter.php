<?php

namespace App\AppModule\Presenters;

use App\Components\Auth\ConnectControl;
use App\Components\Auth\ISetPasswordControlFactory;
use App\Components\Auth\SetPasswordControl;
use App\Components\User\IPreferencesControlFactory;
use App\Components\User\PreferencesControl;
use App\Model\Facade\UserFacade;

class ProfilePresenter extends BasePresenter
{

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var ISetPasswordControlFactory @inject */
	public $iSetPasswordControlFactory;

	/** @var IPreferencesControlFactory @inject */
	public $iPreferencesControlFactory;

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('view')
	 */
	public function actionDefault()
	{
		
	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('delete')
	 */
	public function actionDelete()
	{
		$this->userFacade->hardDelete($this->user->id);
		$this->user->logout();
		$this->flashMessage('Your account has been deleted', 'success');
		$this->redirect(":Front:Homepage:");
	}

	/**
	 * @secured
	 * @resource('profile')
	 * @privilege('settings')
	 */
	public function actionSettings()
	{
//		$this['auth']->setForce();
	}

	// <editor-fold defaultstate="collapsed" desc="components">

	/** @return SetPasswordControl */
	protected function createComponentSetPassword()
	{
		return $this->iSetPasswordControlFactory->create();
	}

	/** @return PreferencesControl */
	protected function createComponentSettings()
	{
		$control = $this->iPreferencesControlFactory->create();
		$control->onAfterSave = function ($savedLanguage) {
			$this->flashMessage('Your settings has been saved.', 'success');
			$this->redirect('this#personal-settings', [ // TODO: toto nastavení se neudrží
				'lang' => $savedLanguage,
			]);
		};
		return $control;
	}

	/** @return ConnectControl */
//	protected function createComponentConnect()
//	{
//		return $this->iConnectControlFactory->create();
//	}
	// </editor-fold>
}
