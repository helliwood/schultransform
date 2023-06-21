<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Question;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use Trollfjord\Bundle\QuestionnaireBundle\Repository\RecommendationRepository;
use Trollfjord\Entity\SchoolType;

class QuestionType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if($options['new']) {
            $builder->add('type', ChoiceType::class, [
                'placeholder' => '-- Bitte wählen --',
                'choices' => [
                    'Selbsteinschätzung' => 'opinion_scale',
                    'Multiple-Choice' => 'multiple_choice',
                    'Freitext' => 'long_text'
                ]
            ]);
        }
        $builder
            ->add('question', TextareaType::class)
            ->add('recommendation', EntityType::class, [
                'class' => Recommendation::class,
                'placeholder' => '-- Bitte wählen --',
                'query_builder' => function (RecommendationRepository $rr) {
                    return $rr->createQueryBuilder('r')
                        ->where('r.advanced = FALSE')
                        ->orderBy('r.title', 'ASC');
                },
                'required' => false,
            ])
            ->add('advancedRecommendation', EntityType::class, [
                'class' => Recommendation::class,
                'placeholder' => '-- Bitte wählen --',
                'query_builder' => function (RecommendationRepository $rr) {
                    return $rr->createQueryBuilder('r')
                        ->where('r.advanced = TRUE')
                        ->orderBy('r.title', 'ASC');
                },
                'required' => false,
            ])
            ->add('schoolTypes', EntityType::class, [
                'class' => SchoolType::class,
                'choice_label' => static function (SchoolType $schoolType) {
                    return $schoolType->getName();
                },
                'expanded' => 'true',
                'multiple' => 'false'
            ])->add('overrides', CollectionType::class, [
                'entry_type' => QuestionOverrideType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'constraints' => [new Valid()]
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'new' => false
        ]);
    }
}
