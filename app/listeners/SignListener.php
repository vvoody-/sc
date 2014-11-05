<?php

namespace App\Listeners;

use App\Mail\Messages\SuccessRegistrationMessage;
use App\Mail\Messages\VerificationMessage;
use App\Model\Entity\Role;
use App\Model\Entity\SignUp;
use App\Model\Entity\User;
use App\Model\Facade\RoleFacade;
use App\Model\Facade\UserFacade;
use App\Model\Storage\SignUpStorage;
use App\TaggedString;
use Kdyby\Events\Subscriber;
use Nette\Application\Application;
use Nette\Application\UI\Control;
use Nette\Latte\Engine;
use Nette\Mail\IMailer;
use Nette\Object;
use Nette\Security\Identity;

class SignListener extends Object implements Subscriber
{

	const REDIRECT_AFTER_SIGNIN = ':App:Dashboard:';
	const REDIRECT_SIGNIN_PAGE = ':Front:Sign:in';

	/** @var SignUpStorage @inject */
	public $session;

	/** @var UserFacade @inject */
	public $userFacade;

	/** @var RoleFacade @inject */
	public $roleFacade;

	/** @var IMailer @inject */
	public $mailer;

	/** @var Application @inject */
	public $application;

	public function __construct(Application $application)
	{
		$this->application = $application->presenter;
	}

	public function getSubscribedEvents()
	{
		return array(
			'App\Components\Profile\FacebookControl::onSuccess' => 'onStartup',
			'App\Components\Profile\SignUpControl::onSuccess' => 'onStartup',
			'App\Components\Profile\TwitterControl::onSuccess' => 'onStartup',
			'App\Components\Profile\RequiredControl::onSuccess' => 'onExists',
			'App\Components\Profile\SummaryControl::onSuccess' => 'onSuccess'
		);
	}

	public function onStartup(Control $control, User $user)
	{
		if ($user->id) {
			$this->onSuccess($control, $user);
		} else {
			$this->session->user = $user;
			$this->onRequire($control, $user);
		}
	}

	public function onRequire(Control $control, User $user)
	{
		if (!$user->mail) {
			$control->presenter->redirect(':Front:Sign:up', [
				'step' => 'required'
			]);
		} else {
			$this->onExists($control, $user);
		}
	}

	public function onExists(Control $control, User $user)
	{
		if (!$existing = $this->userFacade->findByMail($user->mail)) {
			$this->onVerify($control, $user);
		} else {
			$message = new TaggedString('<%mail%> is already registered.', ['mail' => $user->mail]); // ToDo: Translator this can do, I think.
			$control->presenter->flashMessage($message);
			$control->presenter->redirect(self::REDIRECT_SIGNIN_PAGE);
		}
	}

	public function onVerify(Control $control, User $user)
	{
		if ($this->session->isVerified()) {
			$user = $this->userFacade->signUp($user);
			$control->presenter->user->login(new Identity($user->id, $user->getRolesPairs(), $user->toArray()));
			$control->presenter->flashMessage('Your e-mail has been seccessfully verified!', 'success');
			$control->presenter->redirect(':Front:Sign:up', [
				'step' => 'additional'
			]);
		} else {
			$role = $this->roleFacade->findByName($this->session->role);

			// Sign up temporarily
			$signUp = new SignUp();
			$signUp->setMail($user->mail)
					->setHash($user->hash)
					->setName($user->name)
					->setRole($role);

			if ($user->facebook) {
				$signUp->setFacebookId($user->facebook->id)
						->setFacebookAccessToken($user->facebook->accessToken);
			}

			if ($user->twitter) {
				$signUp->setTwitterId($user->twitter->id)
						->setTwitterAccessToken($user->twitter->accessToken);
			}

			$signUp = $this->userFacade->signUpTemporarily($signUp);

			// Send verification e-mail
			$latte = new Engine;
			$params = ['link' => $this->application->presenter->link('//:Front:Sign:verify', $signUp->verificationToken)];
			$message = new VerificationMessage();
			$message->addTo($user->mail)
					->setHtmlBody($latte->renderToString($message->getPath(), $params));

			$this->mailer->send($message);

			$control->presenter->flashMessage('We have sent you a verification e-mail. Please check your inbox!', 'success');
			$control->presenter->redirect(self::REDIRECT_SIGNIN_PAGE);
		}
	}

	public function onSuccess(Control $control, User $user)
	{
		// Send registration e-mail
		$latte = new Engine;
		$message = new SuccessRegistrationMessage();
		$message->addTo($user->mail)
				->setHtmlBody($latte->renderToString($message->getPath()));

		$this->mailer->send($message);

		$control->presenter->restoreRequest($control->presenter->backlink);
		$control->presenter->redirect(self::REDIRECT_AFTER_SIGNIN);
	}

}
