<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Form;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\QuestionChoiceOverride;
use Trollfjord\Entity\SchoolType;

class QuestionChoiceOverrideType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('schoolType', EntityType::class, [
                'class' => SchoolType::class,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('o')
                        ->where('o.name IN (:names)')
                        ->setParameter('names', $options['school_types']);
                },
                'placeholder' => '-- Bitte wählen --',
                'required' => false,
            ])
            ->add('choice', TextareaType::class)
            ->add('hide', CheckboxType::class, ['label' => 'Im Fragebogen ausblenden']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('school_types');
        $resolver->setAllowedTypes('school_types', ['array', Collection::class]);
        $resolver->setDefaults([
            'data_class' => QuestionChoiceOverride::class
        ]);
    }
}
