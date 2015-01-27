<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BaseType extends AbstractType
{
    const NAME = 'block';

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        // add the view to itself vars to allow get it using 'block' variable in a rendered, for example TWIG
        $view->vars['block'] = $view;
        // replace attributes if specified ('attr' variable always exists in a view because it is added by FormView)
        if (isset($options['attr'])) {
            $view->vars['attr'] = $options['attr'];
        }
        // add label text and attributes if specified
        if (isset($options['label'])) {
            $view->vars['label'] = $options['label'];
            if (isset($options['label_attr'])) {
                $view->vars['label_attr'] = $options['label_attr'];
            }
        }
        // add the translation domain
        $view->vars['translation_domain'] = $this->getTranslationDomain($view, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['attr', 'label', 'label_attr', 'translation_domain']);
        $resolver->setAllowedTypes(
            [
                'attr'       => 'array',
                'label_attr' => 'array',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockView $view
     * @param array     $options
     *
     * @return string
     */
    protected function getTranslationDomain(BlockView $view, array $options)
    {
        $translationDomain = isset($options['translation_domain'])
            ? $options['translation_domain']
            : null;
        if ($view->parent) {
            if (!$translationDomain) {
                $translationDomain = $view->parent->vars['translation_domain'];
            }
        }
        if (!$translationDomain) {
            $translationDomain = 'messages';
        }

        return $translationDomain;
    }
}
