<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class OriginFolderFilterProvider
{
    const EMAIL_ORIGIN = 'OroEmailBundle:EmailOrigin';

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var OroEntityManager
     */
    protected $em;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param OroEntityManager    $em
     * @param SecurityContext     $securityContext
     * @param TranslatorInterface $translator
     */
    public function __construct(
        OroEntityManager $em,
        SecurityContext $securityContext,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->securityContext = $securityContext;
        $this->translator = $translator;
    }

    /**
     * Get marketing list types choices.
     *
     * @return array
     */
    public function getListTypeChoices()
    {
        $origins = $this->getOrigins();
        $results = [];
        foreach ($origins as $origin) {
            $folders = $origin->getFolders();
            $mailbox = $origin->getMailboxName();
            if (count($folders) > 0) {
                $results[$mailbox]= [];
                $results[$mailbox]['active'] = $origin->isActive();
                foreach ($folders as $folder) {
                    $results[$mailbox]['folder'][$folder->getId()] = $folder->getFullName();
                }
            }
        }

        return $results;
    }

    /**
     * @return EmailOrigin[]
     */
    protected function getOrigins()
    {
        $criteria = [
            'owner' => $this->securityContext->getToken()->getUser(),
            'isActive' => true,
        ];

        return $this->em->getRepository(self::EMAIL_ORIGIN)->findBy($criteria);
    }
}
