<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Trollfjord\Bundle\MediaBaseBundle\Component\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;

class MediaType extends AbstractType
{
    /**
     * @var MediaService
     */
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // hidden fields cannot have a required attribute
            'required' => false,
            // Pass errors to the parent
            'error_bubbling' => true,
            'compound' => false
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity'] = null;
        $view->vars['icon'] = null;
        $view->vars['parent'] = null;
        if ((int)$view->vars["value"] > 0) {
            $view->vars['entity'] = $this->mediaService->getMediaById($view->vars["value"]);
            if($view->vars['entity']) {
                $view->vars['icon'] = $this->mediaService->getIcons()[$view->vars['entity']->getExtension()];
                $view->vars['parent'] = $view->vars['entity']->getParent();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'hidden';
    }
}
