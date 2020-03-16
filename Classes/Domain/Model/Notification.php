<?php
namespace Swisscom\CommunicationDispatcher\Domain\Model;

/*
 * This file is part of the Swisscom.CommunicationDispatcher package.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Party\Domain\Model\Person;

/**
 * @Flow\Entity
 * @ORM\InheritanceType("JOINED")
 */
class Notification
{

    /**
     * @var Person
     * @ORM\ManyToOne
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $person;

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $text = '';

    /**
     * @var boolean
     */
    protected $notified = false;

    /**
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * Notification constructor.
     * @param Person $person
     * @param string $subject
     * @param string $text
     */
    public function __construct(Person $person, $subject, $text)
    {
        $this->timestamp = new \DateTime();
        $this->person = $person;
        $this->subject = $subject;
        $this->text = $text;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return bool
     */
    public function isNotified()
    {
        return $this->notified;
    }

    /**
     * @param bool $notified
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

}