<?php

namespace App\AppModule\Presenters;

use App\Components\Cv\IBasicInfoControlFactory;
use App\Components\Cv\ILivePreviewControlFactory;
use App\Components\Cv\ISkillsControlFactory;
use App\Components\Cv\LivePreviewControl;
use App\Components\Cv\SkillsControl;
use App\Model\Entity\Cv;
use App\Model\Entity\Skill;
use App\Model\Facade\CvFacade;
use App\TaggedString;
use Exception;

/**
 * 
 */
class CvEditorPresenter extends BasePresenter
{

	/** @persistent int */
	public $id = NULL;

	// <editor-fold defaultstate="collapsed" desc="inject">

	/** @var CvFacade @inject */
	public $cvFacade;

	/** @var ISkillsControlFactory @inject */
	public $iSkillsControlFactory;

	/** @var IBasicInfoControlFactory @inject */
	public $iBasicInfoControlFactory;

	/** @var ILivePreviewControlFactory @inject */
	public $iLivePreviewControlFactory;

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="variables">

	/** @var Cv */
	private $cv;

	// </editor-fold>

	protected function startup()
	{
		parent::startup();
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->cv = $this->cv;
	}

	private function getCv($id)
	{
		try {
			$this->setCv($id);
		} catch (CvEditorPresenterException $ex) {
			$this->flashMessage($ex->getMessage(), 'danger');
			$this->redirect('Dashboard:');
		}
		return $this->cv;
	}

	private function setCv($id)
	{
		if ($this->cv) {
			return;
		}
		$candidate = $this->user->identity->candidate;
		
		if ($id) {
			$cvDao = $this->em->getDao(Cv::getClassName());
			$findedCv = $cvDao->find($id);
			$isOwnCv = $candidate && $findedCv->candidate->id === $candidate->id;
			$canEditForeignCv = $findedCv && $this->user->isAllowed('cvEditor', 'editForeign');
			if ($isOwnCv || $canEditForeignCv) {
				$this->cv = $findedCv;
			}
		} else if ($candidate) { // pro kandidáta načti defaultní
			$this->cv = $this->cvFacade->getDefaultCv($candidate);
		}
		
		if (!$this->cv) {
			throw new CvEditorPresenterException('Requested CV wasn\'t found.');
		}
	}

	/**
	 * @secured
	 * @resource('cvEditor')
	 * @privilege('default')
	 */
	public function actionDefault($id = NULL, $withPreview = TRUE)
	{
		$this->getCv($id);
		$this->template->showPreview = $withPreview;
	}

	/**
	 * @secured
	 * @resource('cvEditor')
	 * @privilege('skills')
	 */
	public function actionSkills($id = NULL)
	{
		$this->getCv($id);
	}

	/**
	 * @secured
	 * @resource('cvEditor')
	 * @privilege('jobs')
	 */
	public function actionJobs($id = NULL)
	{
		$this->getCv($id);
		$this->template->matchedJobs = $this->cvFacade->findJobs($this->cv);
	}

	// <editor-fold defaultstate="collapsed" desc="forms">

	/** @return BasicInfoControl */
	public function createComponentBasicInfoForm()
	{
		$control = $this->iBasicInfoControlFactory->create();
		$control->setAjax(TRUE, TRUE);
		$control->setCv($this->cv);
		$control->onAfterSave = function (Cv $saved) {
			$message = new TaggedString('Cv \'%s\' was successfully saved.', (string) $saved);
			$this->flashMessage($message, 'success');

			if ($this->isAjax()) {
				$this['cvPreview']->redrawControl();
				$this->redrawControl();
			} else {
				$this->redirect('this');
			}
		};
		return $control;
	}

	/** @return SkillsControl */
	public function createComponentSkillsForm()
	{
		$control = $this->iSkillsControlFactory->create();
		$control->setCv($this->cv);
		$control->onAfterSave = function (Cv $saved) {
			$message = new TaggedString('Cv \'%s\' was successfully saved.', (string) $saved);
			$this->flashMessage($message, 'success');
			$this->redirect('this');
		};
		return $control;
	}

	// </editor-fold>
	// <editor-fold defaultstate="collapsed" desc="preview">

	/** @return LivePreviewControl */
	public function createComponentCvPreview()
	{
		$control = $this->iLivePreviewControlFactory->create();
		$control->setCv($this->cv);
		return $control;
	}

	// </editor-fold>
}

class CvEditorPresenterException extends Exception
{
	
}
