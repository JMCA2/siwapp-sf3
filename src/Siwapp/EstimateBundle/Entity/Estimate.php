<?php

namespace Siwapp\EstimateBundle\Entity;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Inflector;
use Siwapp\CoreBundle\Entity\AbstractInvoice;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Siwapp\EstimateBundle\Entity\Estimate
 *
 * @ORM\Table(indexes={
 *    @ORM\Index(name="estimate_cstnm_idx", columns={"customer_name"}),
 *    @ORM\Index(name="estimate_cstid_idx", columns={"customer_identification"}),
 *    @ORM\Index(name="estimate_cstml_idx", columns={"customer_email"}),
 *    @ORM\Index(name="estimate_cntct_idx", columns={"contact_person"})
 * })
 * @ORM\Entity(repositoryClass="Siwapp\EstimateBundle\Repository\EstimateRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Estimate extends AbstractInvoice
{
    /**
     * @ORM\ManyToMany(targetEntity="Siwapp\CoreBundle\Entity\Item", cascade={"persist"})
     * @ORM\JoinTable(name="estimates_items",
     *      joinColumns={@ORM\JoinColumn(name="estimate_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", unique=true)}
     * )
     * @Assert\NotBlank()
     */
    protected $items;

    /**
     * @var integer $number
     *
     * @ORM\Column(name="number", type="integer", nullable=true)
     */
    private $number;

    /**
     * @var boolean $sent_by_email
     *
     * @ORM\Column(name="sent_by_email", type="boolean", nullable=true)
     */
    private $sent_by_email;

    /**
     * @var date $issue_date
     *
     * @ORM\Column(name="issue_date", type="date", nullable=true)
     * @Assert\Date()
     */
    private $issue_date;

    public function __construct()
    {
        parent::__construct();
        $this->issue_date = new \DateTime();
    }

    /**
     * Set draft
     *
     * @param boolean $draft
     */
    public function setDraft($draft)
    {
        $this->draft = $draft;
    }

    /**
     * Get draft
     *
     * @return boolean
     */
    public function isDraft()
    {
        return $this->status == self::DRAFT;
    }

    /**
     * Get draft
     *
     * @return boolean
     */
    public function isApproved()
    {
        return $this->status == self::APPROVED;
    }

    /**
     * Set number
     *
     * @param integer $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set sent_by_email
     *
     * @param boolean $sentByEmail
     */
    public function setSentByEmail($sentByEmail)
    {
        $this->sent_by_email = $sentByEmail;
    }

    /**
     * Get sent_by_email
     *
     * @return boolean
     */
    public function getSentByEmail()
    {
        return $this->sent_by_email;
    }

    /**
     * Set issue_date
     *
     * @param date $issueDate
     */
    public function setIssueDate($issueDate)
    {
        $this->issue_date = $issueDate instanceof \DateTime ?
	  $issueDate: new \DateTime($issueDate);
    }

    /**
     * Get issue_date
     *
     * @return date
     */
    public function getIssueDate()
    {
        return $this->issue_date;
    }

    /** ********** CUSTOM METHODS AND PROPERTIES ************* */


    /**
     * try to catch custom methods to be used in twig templates
     */
    public function __get($name)
    {
        $method = Inflector::camelize("get_{$name}");
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        return false;
    }

    public function __toString()
    {
        return $this->label();
    }
    public function label()
    {
        $series = $this->getSerie();
        $label = '';
        $label .= $series ? $series->getValue() : '';
        $label .= $this->isDraft() ? '[draft]' : $this->getNumber();

        return $label;
    }

    const DRAFT    = 0;
    const PENDING  = 1;
    const APPROVED = 2;
    const REJECTED = 3;

    public function getStatusString()
    {
        switch($this->status)
        {
          case self::DRAFT;
            $status = 'draft';
             break;
          case self::REJECTED;
            $status = 'rejected';
            break;
          case self::APPROVED;
            $status = 'approved';
            break;
          case self::PENDING:
            $status = 'pending';
            break;
          default:
            $status = 'unknown';
            break;
        }
        return $status;
    }

    public function checkStatus()
    {
        if($this->isDraft())
        {
            $this->setStatus(Estimate::DRAFT);
        }
    }

    /* ********** LIFECYCLE CALLBACKS *********** */

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setNextNumber($event)
    {
        // compute the number of invoice
        if( (!$this->number && $this->status!=self::DRAFT) ||
            ($event instanceof PreUpdateEventArgs && $event->hasChangedField('serie') && $this->status!=self::DRAFT)
            )
        {
            $this->setNumber($event->getEntityManager()->getRepository('SiwappEstimateBundle:Estimate')->getNextNumber($this->getSerie()));
        }
    }
}
