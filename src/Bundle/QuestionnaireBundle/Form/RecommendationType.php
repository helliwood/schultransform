<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Recommendation;
use function chr;
use function html_entity_decode;
use function is_string;
use function method_exists;
use function preg_replace;
use function str_replace;
use function ucfirst;

class RecommendationType extends AbstractType
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new CallbackTransformer(
            function (Recommendation $recommendation) {
                return $recommendation;
            },
            function (Recommendation $recommendation) {
                foreach ($recommendation->toArray(0) as $field => $value) {
                    if (is_string($value) && method_exists($recommendation, "set" . ucfirst($field))) {
                        $value = str_replace("&nbsp;", " ", $value);
                        $value = html_entity_decode($value, null, "UTF-8");
                        $value = preg_replace('/' . chr(226) . chr(128) . chr(175) . '/', ' ', $value);
                        // remove word space
                        $recommendation->{"set" . ucfirst($field)}($value);
                    }
                }
                return $recommendation;
            }
        );

        $builder
            ->add('advanced', CheckboxType::class, [
                'label'    => 'Advanced?',
                'required' => false,
            ])
            ->add('description', TextareaType::class, ['label' => 'Bedeutung für den Transformationsprozess'])
            ->add('title', null, ['label' => 'Titel'])
            ->add('description', TextareaType::class, ['label' => 'Bedeutung für den Transformationsprozess'])
            ->add('implementation', TextareaType::class, ['label' => 'Umsetzung'])
            ->add('tips', TextareaType::class, ['label' => 'Tipps für Schulentwicklungsteams'])
            ->add('tipsSchoolManagement', TextareaType::class, ['label' => 'Tipps für Schulleitungen'])
            ->add('examples', TextareaType::class, ['label' => 'Beispiele'])
            ->add('additionalInformation', TextareaType::class, ['label' => 'Weiterführende Informationen'])
            ->addModelTransformer($transformer);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recommendation::class,
        ]);
    }
}
