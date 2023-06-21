<?php

namespace Trollfjord\Bundle\GlossaryBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Trollfjord\Bundle\GlossaryBundle\Entity\Glossary;
use Trollfjord\Bundle\MediaBaseBundle\Component\Form\Type\MediaType;


class GlossaryWord extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('word', TextType::class, [
                'label' => 'Wort',
                'constraints' => [new NotBlank()],
                'required' => true,
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'constraints' => [new NotBlank()],
                'required' => true,
            ])
            ->add('relatedWords', TextType::class,
                ['label' => 'Synonyme', 'required'=> false,'help'=>'Verwenden Sie dieses Feld, um gleichbedeutende Wörter oder andere Schreibweisen mit diesem Synonym zu verknüpfen. Diese Wörter werden automatisch mit dem Synonym verlinkt. Bitte ein Komma als Worttrenner verwenden (z. B. Häuser, Häuschen…).'])
            ->add('theme', TextType::class, ['label' => 'Thema','required' => false,])
            ->add('short_description', TextareaType::class, ['label' => 'Kurzbeschreibung','required' => false,])
            ->add('definition', TextareaType::class, [
                'label' => 'Bedeutung',
                'constraints' => [new NotBlank()],
                'required' => true,
            ])
            ->add('image', MediaType::class, [
                'label' => 'Bild',
                "attr" => [
                    "filetype" => 'image',
                ]
            ])
            ->add('letter_group', HiddenType::class, ['label' => 'Buchstabengruppe'])
            ->add('save', SubmitType::class, ['label' => 'Speichern']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Glossary::class
        ]);
    }
}
