<?php

namespace Oro\Bundle\EmailBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;

class AutoResponseCommand extends ContainerAwareCommand
{
    const OPTION_ID = 'id';

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:email:autoresponse')
            ->setDescription('Responds to email')
            ->addOption(
                static::OPTION_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'The identifier of email system will respond.'
            );
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emailId = $input->getOption(static::OPTION_ID);
        $email = $this->getEmailRepository()->find($emailId);
        $this->getAutoResponseManager()->sendAutoResponses($email);

        $output->writeln(sprintf('<info>Auto responses sent for email - %s</info>', $emailId));
    }

    /**
     * @return EntityRepository
     */
    protected function getEmailRepository()
    {
        return $this->getRegistry()->getRepository(Email::ENTITY_CLASS);
    }

    /**
     * @return Registry
     */
    protected function getRegistry()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->getContainer()->get('oro_email.autoresponserule_manager');
    }
}
