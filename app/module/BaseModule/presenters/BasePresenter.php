<?php

namespace App\BaseModule\Presenters;

use App\Components\Auth\ISignOutControlFactory;
use App\Components\Auth\SignOutControl;
use App\Model\Entity;
use App\Model\Facade\UserFacade;
use App\Model\Storage\UserSettingsStorage;
use App\TaggedString;
use GettextTranslator\Gettext;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;
use Nette\Security\IUserStorage;
use WebLoader\LoaderFactory;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{
	// <editor-fold defaultstate="expanded" desc="constants & variables">

	/** @persistent string */
	public $lang = 'en';

	/** @persistent */
	public $backlink = '';

	/** @var LoaderFactory @inject */
	public $webLoader;

	/** @var ISignOutControlFactory @inject */
	public $iSignOutControlFactory;

	/** @var Gettext @inject */
	public $translator;

	/** @var UserSettingsStorage @inject */
	public $settingsStorage;

	/** @var EntityManager @inject */
	public $em;

	/** @var UserFacade @inject */
	public $userFacade;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
		$this->setLang();
	}

	protected function beforeRender()
	{
		$this->template->lang = $this->lang;
		$this->template->setTranslator($this->translator);
		$this->template->designSettings = new Entity\PageDesignSettings();
	}

	// <editor-fold defaultstate="collapsed" desc="flash messages">

	/**
	 * Translate flash messages if not HTML
	 * @param type $message
	 * @param type $type
	 */
	public function flashMessage($message, $type = 'info')
	{
		if (is_string($message)) {
			$message = $this->translator->translate($message);
		} else if ($message instanceof TaggedString) {
			$message->setTranslator($this->translator);
			$message = (string) $message;
		}
		parent::flashMessage($message, $type);
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="requirments">

	public function checkRequirements($element)
	{
		$secured = $element->getAnnotation('secured');
		$resource = $element->getAnnotation('resource');
		$privilege = $element->getAnnotation('privilege');

		if ($secured) {
			if (!$this->user->loggedIn) {
				if ($this->user->logoutReason === IUserStorage::INACTIVITY) {
					$this->flashMessage('You have been signed out, because you have been inactive for long time.');
					$this->redirect(':Front:LockScreen:', array('backlink' => $this->storeRequest()));
				} else {
					$this->flashMessage('You should be logged in!');
					$this->redirect(':Front:Sign:in', ['backlink' => $this->storeRequest()]);
				}
			} elseif (!$this->user->isAllowed($resource, $privilege)) {
				throw new ForbiddenRequestException;
			}
		}
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="language">
	private function setLang()
	{
		// Update settings when changes
		if ($this->lang !== $this->settingsStorage->language) {
			$this->settingsStorage
					->setLanguage($this->lang)
					->save();
		}

		$this->translator->setLang($this->lang);
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="components">

	/** @return SignOutControl */
	public function createComponentSignOut()
	{
		return $this->iSignOutControlFactory->create();
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="css webloader">

	/** @return CssLoader */
	protected function createComponentCssFront()
	{
		$css = $this->webLoader->createCssLoader('front')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssAdmin()
	{
		$css = $this->webLoader->createCssLoader('admin')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssMetronicCore()
	{
		$css = $this->webLoader->createCssLoader('metronicCore')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssMetronicPlugin()
	{
		$css = $this->webLoader->createCssLoader('metronicPlugin')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssMetronicTheme()
	{
		$css = $this->webLoader->createCssLoader('metronicTheme')
				->setMedia('screen,projection,tv');
		return $css;
	}

	/** @return CssLoader */
	protected function createComponentCssPrint()
	{
		$css = $this->webLoader->createCssLoader('print')
				->setMedia('print');
		return $css;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="js webloader">

	/** @return JavaScriptLoader */
	protected function createComponentJsApp()
	{
		return $this->webLoader->createJavaScriptLoader('app');
	}

	/** @return JavaScriptLoader */
	protected function createComponentJsAppPlugins()
	{
		return $this->webLoader->createJavaScriptLoader('appPlugins');
	}

	/** @return JavaScriptLoader */
	protected function createComponentJsMetronicPlugins()
	{
		return $this->webLoader->createJavaScriptLoader('metronicPlugins');
	}

	/** @return JavaScriptLoader */
	protected function createComponentJsMetronicCore()
	{
		return $this->webLoader->createJavaScriptLoader('metronicCore');
	}

	/** @return JavaScriptLoader */
	protected function createComponentJsMetronicCoreIE9()
	{
		return $this->webLoader->createJavaScriptLoader('metronicCoreIE9');
	}

	// </editor-fold>
}
