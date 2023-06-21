<?php

namespace Trollfjord\Bundle\QuestionnaireBundle\Form;

use MongoDB\BSON\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QuestionnaireForm extends AbstractType
{


    /**
     * @var string[]
     */
    public static array $config = [
        [

            'items' => [
                "multiple_choice" => [
                    'type' => ChoiceType::class,
                    'label' => 'Schülerzahl aktuell',
                    'required' => true,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 1999],
                    'validation' => [['PotentialCurrent' => 'Vergleich mit Summe aller Jahrgänge']]
                ],
                "potential_feature" => [
                    'type' => IntegerType::class,
                    'label' => 'Schülerzahl Prognose in 2 Jahren',
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 1999]
                ],

            ]
        ]
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('Choise', ChoiceType::class, [
                    'label' => "Frage.....?",
                    'expanded' => true,
                    'required' => true,
                    'choices' => $this->getChoices(8)
                ]
            )
            ->add('Ok', SubmitType::class);
    }

    /**
     * @param $questionData
     */
    private function getQuestionType($questionData)
    {
        $formType = null;
        switch ($questionData['type']) {
            case 'opinion_scale':
                $formType = $this->multipleChoice($questionData);
                break;
            case 'multiple_choice':
                echo ""; //e. $this->multipleChoice()
                break;
        }


    }

    //{"start_at_one":false,"steps":8,"labels":{"left":"trifft nicht zu","center":"trifft teilweise zu","right":"trifft voll und ganz zu"}}
    private function multipleChoice($questionData): array
    {
        $config = [
            ChoiceType::class, [
                'label' => $questionData['question'],
                'expanded' => true,
                'required' => true,
                'choices' => $this->getChoices(8),
            ]

        ];


        return $config;

    }

    //help to retrieve the multiple choices from the question data
    private function getChoices($numberOfOptions)
    {
        $choices = [];
        for ($i = 0; $i < $numberOfOptions; $i++) {
            $choices[$i] = $i;
        }
        return $choices;


    }

}

















