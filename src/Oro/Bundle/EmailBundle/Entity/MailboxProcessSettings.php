<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Form\Model\ExtendMailboxProcessSettings;

/**
 * @ORM\Table(
 *      name="oro_email_mailbox_process"
 * )
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=30)
 */
abstract class MailboxProcessSettings extends ExtendMailboxProcessSettings
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns type of process.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
