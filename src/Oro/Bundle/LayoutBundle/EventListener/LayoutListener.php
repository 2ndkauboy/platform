<?php

namespace Oro\Bundle\LayoutBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

/**
 * The LayoutListener class handles the @Layout annotation.
 */
class LayoutListener
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container The service container instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Renders the layout and initializes the content of a new response object
     * with the rendered layout.
     *
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws LogicException if @Layout annotation is used in incorrect way
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        /** @var LayoutAnnotation|null $layoutAnnotation */
        $layoutAnnotation = $request->attributes->get('_layout');
        if (!$layoutAnnotation) {
            return;
        }
        if ($request->attributes->get('_template')) {
            throw new LogicException(
                'The @Template() annotation cannot be used together with the @Layout() annotation.'
            );
        }

        $parameters = $event->getControllerResult();
        if (is_array($parameters)) {
            $context = new LayoutContext();
            foreach ($parameters as $key => $value) {
                $context->set($key, $value);
            }
            $this->configureContext($context, $layoutAnnotation);
            $layout = $this->getLayout($context, $layoutAnnotation);
        } elseif ($parameters instanceof ContextInterface) {
            $this->configureContext($parameters, $layoutAnnotation);
            $layout = $this->getLayout($parameters, $layoutAnnotation);
        } elseif ($parameters instanceof Layout) {
            if (!$layoutAnnotation->isEmpty()) {
                throw new LogicException(
                    'The empty @Layout() annotation must be used when '
                    . 'the controller returns an instance of "Oro\Component\Layout\Layout".'
                );
            }
            $layout = $parameters;
        } else {
            return;
        }

        $response = new Response();
        $response->setContent($layout->render());
        $event->setResponse($response);
    }

    /**
     * Get the layout and add parameters to the layout context.
     *
     * @param ContextInterface $context
     * @param LayoutAnnotation $layoutAnnotation
     *
     * @return Layout
     */
    protected function getLayout(ContextInterface $context, LayoutAnnotation $layoutAnnotation)
    {
        /** @var LayoutManager $layoutManager */
        $layoutManager = $this->container->get('oro_layout.layout_manager');
        $layoutBuilder = $layoutManager->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        $blockThemes = $layoutAnnotation->getBlockThemes();
        if (!empty($blockThemes)) {
            $layoutBuilder->setBlockTheme($blockThemes);
        }

        return $layoutBuilder->getLayout($context);
    }

    /**
     * Configures the layout context.
     *
     * @param ContextInterface $context
     * @param LayoutAnnotation $layoutAnnotation
     */
    protected function configureContext(ContextInterface $context, LayoutAnnotation $layoutAnnotation)
    {
        $action = $layoutAnnotation->getAction();
        if (!empty($action)) {
            $currentAction = $context->getOr('action');
            if (empty($currentAction)) {
                $context->set('action', $action);
            }
        }
        $theme = $layoutAnnotation->getTheme();
        if (!empty($theme)) {
            $currentTheme = $context->getOr('theme');
            if (empty($currentTheme)) {
                $context->set('theme', $theme);
            }
        }

        $vars = $layoutAnnotation->getVars();
        if (!empty($vars)) {
            $context->getResolver()->setRequired($vars);
        }
    }
}
