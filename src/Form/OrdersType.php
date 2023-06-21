<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class OrdersType
 *
 * @author Dirk Mertins <mertins@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class OrdersType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder

            ->add('salutation', ChoiceType::class, ['choices' => ['Frau' => 'Frau', 'Herr' => 'Herr', 'Divers' => 'Divers']])
            ->add('name', TextType::class,
                [
                    'label' => "Ihr Name:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihren Namen an']),
                    ]
                ]
            )
            ->add('phonefax', TextType::class,
                [
                    'label_attr' => ['tabindex' => '-1'],
                    'attr' => ['tabindex' => '-1', 'autocomplete' => 'off'],
                    'constraints' => [],
                    'row_attr' => [
                        'style' => 'height:0px;overflow:hidden;position:absolute;top:-1;left:-1px'
                    ]

                ]
            )
            ->add('organisation', TextType::class,
                [
                    'label' => "Schule:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihre Schule an']),
                    ]
                ]
            )
            ->add('amount', ChoiceType::class, ['label'=>'Anzahl','choices' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']])

            ->add('street', TextType::class,
                [
                    'label' => "Straße",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihre Straße an'])
                    ],
                ]
            )
            ->add('postalcode', TextType::class,
                [
                    'label' => "Postleitzahl:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihre Postleitzahl an'])
                    ],
                ]
            )
            ->add('city', TextType::class,
                [
                    'label' => "Ort:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihre Postleitzahl an'])
                    ],
                ]
            )
            ->add('email', TextType::class,
                [
                    'label' => "E-Mail:",
                    'required' => true,
                    'constraints' => [
                        new Email(['message' => 'Bitte geben Sie eine korrekte E-Mail an']),
                        new NotBlank(['message' => 'Bitte geben Sie Ihre E-Mail an'])
                    ],
                ]
            )
            ->add('message', TextareaType::class,
                [
                    'label' => "Ihre Nachricht:",
                    'required' => true,
                    'constraints' => [
                        new Length([
                            'min' => 1,
                            'max' => 2000,
                            'minMessage' => 'Ihre Nachricht muss mindestens {{ limit }} Zeichen lang sein',
                            'maxMessage' => 'Ihre Nachricht darf maximal {{ limit }} Zeichen lang sein',

                        ])
                    ]
                ]
            )
            ->add('dsgvo', CheckboxType::class,
                [
                    'label' => 'Ich bin damit einverstanden, dass oben stehende Daten entsprechend der <a href="/datenschutz" target="_blank">Datenschutzerklärung</a> in einem automatisierten Verfahren erhoben, gespeichert und verarbeitet werden.',
                    'label_html' => true,
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
                    ]
                ]
            )

            ->add('submit', SubmitType::class, [
                'label' => 'Absenden'
            ]);
    }

}
