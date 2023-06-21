<?php

namespace Trollfjord\Bundle\MediaBaseBundle\Form;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\MediaBaseBundle\Entity\Media;
use Trollfjord\Bundle\MediaBaseBundle\Service\MediaService;
use Trollfjord\Bundle\MediaBaseBundle\Validator\Constraints\ConstrainsTrait;
use Trollfjord\Bundle\MediaBaseBundle\Validator\Constraints\File;

class FileEdit extends AbstractType
{
    use ConstrainsTrait;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var Array
     */
    private $mimeTypes = [];

    public function __construct(MediaService $mediaService, $mimeTypes)
    {
        $this->mediaService = $mediaService;
        $this->mimeTypes = $mimeTypes;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->setConstrain($builder, $this->mimeTypes,['file', 'thumbnail']);

        $builder
            ->add('file', FileType::class,
                [
                    'label' => _("Datei:"),
                    'required' => false,
                    'constraints' =>
                        new File([
                            'mimeTypes' => ['denyAllFiles'],
                            'mimeTypesMessage' => 'Dateitype nicht erlaubt'
                        ])
                ]
            )
            ->add('thumbnail', FileType::class,
                [
                    'label' => _("Alternativbild:"),
                    'required' => false,
                    'constraints' =>
                        new File([
                            'mimeTypes' => ['denyAllFiles'],
                            'mimeTypesMessage' => 'Dateitype nicht erlaubt'
                        ])
                ]
            )
            ->add('name', TextType::class, ['label' => _("Name:")])
            ->add('parent', ChoiceType::class, [
                'choices' => $this->mediaService->getFoldersAsDepthArray(),
                'choice_label' => function(Media $media) {
                    return $media->getOptViewName();
                }
            ])
            ->add('copyright', TextType::class, ['label' => _("Copyright:")])
            ->add('description', TextareaType::class, ['label' => _("Beschreibung:")])
            ->add('save', SubmitType::class)
        ;
    }
}