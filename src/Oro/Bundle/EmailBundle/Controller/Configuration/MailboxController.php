<?php

namespace Oro\Bundle\EmailBundle\Controller\Configuration;

use FOS\RestBundle\Controller\Annotations\Delete;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class MailboxController extends Controller
{
    const ACTIVE_GROUP    = 'platform';
    const ACTIVE_SUBGROUP = 'email_configuration';

    /**
     * @Route(
     *      "/system/platform/email_configuration/mailbox/update/{id}",
     *      name="oro_email_mailbox_update"
     * )
     * @Template
     * @AclAncestor("oro_email_mailbox_update")
     *
     * @param Mailbox $mailbox
     * @param Request $request
     *
     * @return array
     */
    public function updateAction(Mailbox $mailbox, Request $request)
    {
        return $this->update($mailbox, $request);
    }

    /**
     * Prepares and handles data of Mailbox update/create form.
     *
     * @param Mailbox $mailbox
     * @param Request $request
     *
     * @return array
     */
    private function update(Mailbox $mailbox, Request $request)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups(self::ACTIVE_GROUP, self::ACTIVE_SUBGROUP);

        $tree = $provider->getTree();

        $handler = $this->get('oro_email.form.handler.mailbox');

        if ($handler->process($mailbox)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans(
                    'oro.email.mailbox.action.saved',
                    ['%mailbox%' => $mailbox->getLabel()]
                )
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                [
                    'route' => 'oro_email_mailbox_update',
                    'parameters' => ['id' => $mailbox->getId()]
                ],
                $this->getRedirectData($request)
            );
        }

        return [
            'data'           => $tree,
            'form'           => $handler->getForm()->createView(),
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
            'redirectData'   => $this->getRedirectData($request),
        ];
    }

    /**
     * @Route(
     *      "/system/platform/email_configuration/mailbox/create",
     *      name="oro_email_mailbox_create"
     * )
     * @Template("OroEmailBundle:Configuration/Mailbox:update.html.twig")
     * @AclAncestor("oro_email_mailbox_create")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $data = new Mailbox();

        return $this->update($data, $request);
    }

    /**
     * @Delete("/system/platform/email_configuration/mailbox/delete/{id}", name="oro_email_mailbox_delete")
     * @ParamConverter("mailbox", class="OroEmailBundle:Mailbox")
     * @AclAncestor("oro_email_mailbox_delete")
     *
     * @param Mailbox $mailbox
     *
     * @return Response
     */
    public function deleteAction(Mailbox $mailbox)
    {
        $mailboxManager = $this->getDoctrine()->getManagerForClass('OroEmailBundle:Mailbox');
        $mailboxManager->remove($mailbox);
        $mailboxManager->flush();

        return new Response(Response::HTTP_OK);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getRedirectData(Request $request)
    {
        return $request->query->get(
            'redirectData',
            [
                'route' => 'oro_config_configuration_system',
                'parameters' => [
                    'activeGroup' => self::ACTIVE_GROUP,
                    'activeSubGroup' => self::ACTIVE_SUBGROUP,
                ]
            ]
        );
    }
}
