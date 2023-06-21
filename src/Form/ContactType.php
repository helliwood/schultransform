<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ContactType
 *
 * @author Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class ContactType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', TextType::class,
                [
                    'label' => "Ihr Name:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message'=>'Bitte geben Sie Ihren Namen an']),
                    ]
                ]
            )
            ->add('organisation', TextType::class,
                [
                    'label' => "Ihre Einrichtung / Organisation:",
                    'required' => false,
                    'constraints' => [
                    ]
                ]
            )
            ->add('email', TextType::class,
                [
                    'label' => "E-Mail:",
                    'required' => true,
                    'constraints' => [
                        new Email(['message'=>'Bitte geben Sie eine korrekte E-Mail an']),
                        new NotBlank(['message'=>'Bitte geben Sie Ihre E-Mail an'])
                    ],
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
            ->add('subject', TextType::class,
                [
                    'label' => "Betreff:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message'=>'Bitte geben Sie einen Betreff an.']),
                    ]
                ]
            )
            ->add('message', TextareaType::class,
                [
                    'label' => "Ihre Nachricht:",
                    'required' => true,
                    'constraints' => [
                        new NotBlank(['message'=>'Bitte geben Sie eine Nachricht an']),
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
                        new NotBlank(['message'=>'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
                    ]
                ]
            )
            ->add('submit', SubmitType::class,[
                'label'=>'Absenden'
            ]);
    }

}