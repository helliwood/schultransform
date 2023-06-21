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
 * Class RegistrationType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class RegistrationType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', TextType::class,
                [
                    'label' => "Vorname, Nachname:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihren Namen an']),
                    ]
                ]
            )
            ->add('organisation', TextType::class,
                [
                    'label' => "Einrichtung / Institution:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte geben Sie Ihre Einrichtung / Institution an']),
                    ]
                ]
            )

            ->add('phonefax', TextType::class,
                [
                    'label_attr'=>['tabindex'=>'-1'],
                    'attr'=>['tabindex'=>'-1','autocomplete'=>'off'],
                    'constraints' => [],
                    'row_attr' => [
                        'style' => 'height:0px;overflow:hidden;position:absolute;top:-1;left:-1px'
                    ]

                ]
            )

            ->add('email', TextType::class,
                [
                    'label' => "Kontakt-E-Mail:",
                    'required' => true,
                    'constraints' => [
                        new Email(['message' => 'Bitte geben Sie eine korrekte E-Mail an']),
                        new NotBlank(['message' => 'Bitte geben Sie Ihre E-Mail an'])
                    ],
                ]
            )
            ->add('message', TextareaType::class,
                [
                    'label' => "Nachricht:",
                    'required' => false,
                    'constraints' => [
                        new Length([
                            'min' => 0,
                            'max' => 2000,
                            'minMessage' => 'Ihre Nachricht muss mindestens {{ limit }} Zeichen lang sein',
                            'maxMessage' => 'Ihre Nachricht darf maximal {{ limit }} Zeichen lang sein',

                        ])
                    ]
                ]
            )
            ->add('dsgvo', CheckboxType::class,
                [
                    'label' => 'Ich bin damit einverstanden, dass oben stehende Daten entsprechend der <a href="/datenschutz" target="_blank">Datenschutzerklärung</a> in einem automatisierten Verfahren erhoben, gespeichert und verarbeitet werden.*',
                    'label_html' => true,
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
                    ]
                ]
            )
            ->add('allow_contact', CheckboxType::class,
                [
                    'label' => 'Das Projekt Schultransform entwickelt sich stetig weiter. Hiermit willige ich ein, dass mich das Projektbüro Schultransform in diesem Rahmen kontaktieren darf.',
                    'label_html' => true,
                    'required' => true,
                    'constraints' => []
                ]
            )
            ->add('submit', SubmitType::class, [
                'label' => 'Absenden'
            ]);
    }

}