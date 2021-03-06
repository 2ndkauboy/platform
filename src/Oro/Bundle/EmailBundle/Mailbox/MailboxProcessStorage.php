<?php

namespace Oro\Bundle\EmailBundle\Mailbox;

use Oro\Bundle\EmailBundle\Entity\MailboxProcessSettings;

class MailboxProcessStorage
{
    /** @var MailboxProcessProviderInterface[] */
    protected $processes = [];

    /**
     * Registers mailbox process provider with application.
     *
     * @param string                          $type
     * @param MailboxProcessProviderInterface $provider
     */
    public function addProcess($type, MailboxProcessProviderInterface $provider)
    {
        if (isset($this->processes[$type])) {
            throw new \LogicException(
                sprintf('Process of type %s is already registered. Review your service configuration.')
            );
        }

        $this->processes[$type] = $provider;
    }

    /**
     * Returns process provider of provided type.
     *
     * @param string $type
     *
     * @return MailboxProcessProviderInterface
     */
    public function getProcess($type)
    {
        $this->errorIfUnregistered($type);

        return $this->processes[$type];
    }

    /**
     * Returns all registered processes.
     *
     * @return MailboxProcessProviderInterface['type' => MailboxProcessProviderInterface]
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Creates new instance of settings entity for provided type.
     *
     * @param $type
     *
     * @return MailboxProcessSettings
     */
    public function getNewSettingsEntity($type)
    {
        $entityClass = $this->getProcess($type)->getSettingsEntityFQCN();

        if (!class_exists($entityClass)) {
            throw new \LogicException(
                sprintf('Settings entity %s for mailbox process %s does not exist.', $entityClass, $type)
            );
        }

        return new $entityClass();
    }

    /**
     * Returns choice list for process type choice field.
     *
     * @return array['type' => 'Process Type Label (translate id)']
     */
    public function getProcessTypeChoiceList()
    {
        $choices = [];
        foreach ($this->processes as $type => $provider) {
            if (!$provider->isEnabled()) {
                continue;
            }

            $choices[$type] = $provider->getLabel();
        }

        return $choices;
    }

    /**
     * Throws exception if provided type is not registered within storage instance.
     *
     * @param string $type
     */
    protected function errorIfUnregistered($type)
    {
        if (!isset($this->processes[$type])) {
            throw new \LogicException(
                sprintf('There is no mailbox process with type %s registered.', $type)
            );
        }
    }
}
