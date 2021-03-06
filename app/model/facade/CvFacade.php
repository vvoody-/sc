<?php

namespace App\Model\Facade;

use App\Model\Entity\Candidate;
use App\Model\Entity\Cv;
use App\Model\Entity\Job;
use App\Model\Repository\CvRepository;
use App\Model\Repository\JobRepository;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

/**
 * TODO: Test it
 */
class CvFacade extends Object
{

	/** @var EntityManager @inject */
	public $em;

	/** @var CvRepository */
	private $cvDao;

	/** @var JobRepository */
	private $jobDao;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->cvDao = $this->em->getDao(Cv::getClassName());
		$this->jobDao = $this->em->getDao(Job::getClassName());
	}

	// <editor-fold defaultstate="colapsed" desc="create & add & edit">

	/**
	 * Create new Cv for inserted candidate
	 * @param Candidate $candidate
	 * @param type $name
	 * @return Cv
	 */
	public function create(Candidate $candidate, $name = NULL)
	{
		$cv = new Cv($name);
		$cv->candidate = $candidate;
		$cv->isDefault = !$cv->candidate->hasDefaultCv();
		return $this->cvDao->save($cv);
	}

	/**
	 * Set Cv as default and reset other default CV
	 * @param Cv $cv
	 */
	public function setAsDefault(Cv $cv)
	{
		if (!$cv->isDefault) {
			$defaultCv = $cv->candidate->getDefaultCv();
			if ($cv->id && $defaultCv->id !== $cv->id) {
				$defaultCv->isDefault = FALSE;
				$this->em->persist($defaultCv);
			}
			$cv->isDefault = TRUE;
			$this->em->persist($cv);
			$this->em->flush();
		}
	}

	// </editor-fold>
	// <editor-fold defaultstate="colapsed" desc="getters">

	/**
	 * Get default CV or create it
	 * @param Candidate $candidate
	 * @return Cv
	 */
	public function getDefaultCv(Candidate $candidate)
	{
		$defaultCv = $candidate->defaultCv;
		if (!$defaultCv) {
			$cv = new Cv;
			$cv->candidate = $candidate;
			$cv->isDefault = TRUE;
			$this->em->persist($cv);
			$this->em->flush();
			$defaultCv = $cv;
		}
		return $defaultCv;
	}

	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="finders">

	public function findJobs(Cv $cv)
	{
		return $this->jobDao->findBySkillKnows($cv->skillKnows);
	}

	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="checkers">
	// </editor-fold>
	// <editor-fold defaultstate="expanded" desc="delete">
	// </editor-fold>
}
