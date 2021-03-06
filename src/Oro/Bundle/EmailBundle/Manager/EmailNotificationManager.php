<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailNotificationManager
 * @package Oro\Bundle\EmailBundle\Manager
 */
class EmailNotificationManager
{
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var Router */
    protected $router;

    /** @var EmailCacheManager */
    protected $emailCacheManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $entityManager
     * @param HtmlTagHelper $htmlTagHelper
     * @param Router $router
     * @param EmailCacheManager $emailCacheManager
     * @param ConfigManager $configManager
     */
    public function __construct(
        EntityManager $entityManager,
        HtmlTagHelper $htmlTagHelper,
        Router $router,
        EmailCacheManager $emailCacheManager,
        ConfigManager $configManager
    ) {
        $this->em = $entityManager;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->router = $router;
        $this->emailCacheManager = $emailCacheManager;
        $this->configManager = $configManager;
    }

    /**
     * @param User $user
     * @param $maxEmailsDisplay
     *
     * @return array
     */
    public function getEmails(User $user, $maxEmailsDisplay)
    {
        $emails = $this->em->getRepository('OroEmailBundle:Email')->getNewEmails($user, $maxEmailsDisplay);

        $emailsData = [];
        /** @var $email Email */
        foreach ($emails as $email) {
            $isSeen = $email['seen'];
            $email = $email[0];
            $bodyContent = '';
            try {
                $this->emailCacheManager->ensureEmailBodyCached($email);
                $bodyContent = $this->htmlTagHelper->shorten(
                    $this->htmlTagHelper->stripTags(
                        $this->htmlTagHelper->purify($email->getEmailBody()->getBodyContent())
                    )
                );
            } catch (LoadEmailBodyException $e) {
                // no content
            }

            $emailsData[] = [
                'route' => $this->router->generate('oro_email_email_reply', ['id' => $email->getId()]),
                'id' => $email->getId(),
                'seen' => $isSeen,
                'subject' => $email->getSubject(),
                'bodyContent' => $bodyContent,
                'fromName' => $email->getFromName(),
                'linkFromName' => $this->getFromNameLink($email)
            ];
        }

        return $emailsData;
    }

    /**
     * @param Email $email
     *
     * @return bool|string
     */
    protected function getFromNameLink(Email $email)
    {
        $path = false;
        if ($email->getFromEmailAddress() && $email->getFromEmailAddress()->getOwner()) {
            $className = $email->getFromEmailAddress()->getOwner()->getClass();
            $routeName = $this->configManager->getEntityMetadata($className)->getRoute('view', false);
            $path = null;
            try {
                $path = $this->router->generate(
                    $routeName,
                    ['id' => $email->getFromEmailAddress()->getOwner()->getId()]
                );
            } catch (RouteNotFoundException $e) {
                return false;
            }
        }

        return $path;
    }

    /**
     * Get count new emails
     *
     * @param User $user
     *
     * @return integer
     */
    public function getCountNewEmails(User $user)
    {
        return $this->em->getRepository('OroEmailBundle:Email')->getCountNewEmails($user);
    }
}
