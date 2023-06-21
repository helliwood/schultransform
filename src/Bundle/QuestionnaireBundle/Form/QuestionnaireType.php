<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Questionnaire;
use Trollfjord\Bundle\ContentTreeBundle\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;
class QuestionnaireType extends AbstractType
{

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableParameterTypeHintSpecification
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $siteSevice = $options['site_service'];

        $builder
            ->add('name')
            ->add('fullName')
            ->add('icon')
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    'Bitte wählen' => null,
                    'Schulträger' => "school_board",
                    'Schule' => "school",
                ],
            ])
            ->add("site", ChoiceType::class, [
                'label' => 'Link:',
                'placeholder' => '-- Seite wählen --',
                'choices' => $siteSevice->getContentTree4Select(),
                'choice_label' => function (Site $site) {
                    $depth = 0;
                    $parent = $site->getParent();
                    while (! is_null($parent)) {
                        $depth++;
                        $parent = $parent->getParent();
                    }
                    return html_entity_decode(str_pad(
                            $site->getName(),
                            strlen($site->getName()) + ($depth * 12),
                            "&nbsp;",
                            STR_PAD_LEFT)) . " (" . $site->getId() . ")";
                },
                'choice_value' => function ($site) {
                    if (is_string($site)) {
                        return $site;
                    } else {
                        return $site ? $site->getId() : null;
                    }
                }
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
            'data_class' => Questionnaire::class,
        ]);
        $resolver->setRequired('site_service');
    }
}
