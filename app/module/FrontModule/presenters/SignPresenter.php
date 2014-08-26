<?php

namespace App\FrontModule\Presenters;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{
	/** @var \App\Components\ISignInControlFactory @inject */
	public $iSignInControlFactory;
	
	/** @var \App\components\Sign\IAuthControlFactory @inject */
	public $iAuthControlFactory;

	/** @var \App\Model\Storage\RegistrationStorage @inject */
	public $registration;

	/** @var \App\Model\Facade\Registration @inject */
	public $registrationFacade;


	protected function startup()
	{
		parent::startup();

//		$this->user->logout();

		// Logged user redirect away
		if ($this->user->isLoggedIn()) {
//			$this->flashMessage('You have been already signed in.', 'warning'); // ToDo: Delete, 'cos showing after redirection throught this presenter, maybe.
			$this->redirect(':Admin:Dashboard:');
		}
	}

	/**
	 *
	 */
	public function actionDefault()
	{
		$this->redirect('in');
	}

	/**
	 *
	 */
	public function actionIn()
	{
//		$this->registration->wipe();
	}
	
	/**
	 *
	 */
	public function actionOut()
	{
		$this->user->logout();
		$this->redirect(':Front:Sign:in');
	}

	/**
	 *
	 */
	public function actionLostPassword()
	{
		$this->flashMessage('Not implemented yet', 'warning');
		$this->redirect('in');
	}

	/**
	 *
	 */
	public function actionRegister($source = NULL)
	{
		
		if (!$this->registration->isSource($source)) {
			$this->redirect('in');
		} else {
			if ($source === NULL) {
				$this->registration->wipe();
			}
		}
		
		// Check if is user in registration process
//		$this->checkInProcess();

		$this->template->bool = $this->registration->isOAuth();
	}

	/**
	 *
	 */
	public function actionVerify($code)
	{
		if ($user = $this->registrationFacade->verify($code)) {
			$this->presenter->user->login(new \Nette\Security\Identity($user->id, $user->getRolesPairs(), $user->toArray()));
			$this->presenter->flashMessage('You have been successfully logged in!', 'success');
			$this->presenter->redirect(':Admin:Dashboard:');
		} else {
			$this->presenter->flashMessage('Verification code is incorrect.', 'warning');
			$this->redirect('in');
		}


	}

	private function checkInProcess()
	{
		if (!$this->registration->isOAuth()) {
			$this->redirect(':Front:Sign:in');
		}
	}

// <editor-fold defaultstate="collapsed" desc="Components">

	/** @return \App\Components\SignInControl */
	protected function createComponentSignIn()
	{
		return $this->iSignInControlFactory->create();
	}
	
	/** @return \App\components\Sign\AuthControl */
	protected function createComponentAuth()
	{
		return $this->iAuthControlFactory->create();
	}

// </editor-fold>

}
