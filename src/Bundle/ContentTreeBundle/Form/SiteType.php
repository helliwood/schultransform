<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Trollfjord\Bundle\ContentTreeBundle\Form\DataTransformer\MediaToNumberTransformer;
use Trollfjord\Bundle\MediaBaseBundle\Component\Form\Type\MediaType;

/**
 * AddSiteType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Form
 */
class SiteType extends AbstractType
{
    /**
     * @var MediaToNumberTransformer
     */
    private MediaToNumberTransformer $mediaToNumberTransformer;

    /**
     * AddSiteType constructor.
     * @param MediaToNumberTransformer $mediaToNumberTransformer
     */
    public function __construct(MediaToNumberTransformer $mediaToNumberTransformer)
    {
        $this->mediaToNumberTransformer = $mediaToNumberTransformer;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('slug')
            ->add('alternativeRoute')
            ->add('parent')
            ->add('position')
            ->add('socialMediaImage', MediaType::class, [
                "label" => "Social-Media-Bild",
                "attr" => [
                    "filetype" => "image"
                ]
            ])
            ->add('dcTitle')
            ->add('dcCreator')
            ->add('dcDate')
            ->add('dcDescription', TextareaType::class)
            ->add('dcKeywords')
            ->add('menuEntry')
            ->add('published');

        $builder->get('socialMediaImage')->addModelTransformer($this->mediaToNumberTransformer);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Site::class
        ]);
    }
}
