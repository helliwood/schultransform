<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;

/**
 * Class AccountType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class AccountType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('salutation', ChoiceType::class, ['choices' => ['Frau' => 'Frau', 'Herr' => 'Herr', 'Divers' => 'Divers']])
            ->add('firstName', null, [
                'required' => TextType::class,
                'constraints' => [new NotBlank()]
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [new NotBlank()]
            ])
            ->add('email', TextType::class, [
                'constraints' => [new NotBlank()]
            ]);

        if (! $options['user']->hasPassword()) {
            $builder->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                //'mapped' => false,
                'invalid_message' => 'Das Passwort wurde nicht korrekt wiederholt.',
                'first_options' => [
                    'label' => 'Passwort',
                    'attr' => [
                        'autocomplete' => 'new-password'
                    ],
                    'always_empty' => false,
                    'help' => 'Das Passwort muss aus mindestens 8 Zeichen bestehen und mindestens eine Zahl und einen Buchstaben enthalten. Erlaubte Sonderzeichen: !@#$%._-'
                ],
                'second_options' => [
                    'label' => 'Passwort wiederholen',
                    'always_empty' => false
                ],
                'constraints' => [new NotBlank(),
                                  new Regex([
                                      'pattern' => '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%._-]{8,}$/Uism',
                                      'message' => 'Das Passwort erfüllt nicht die Mindestanforderungen oder enthält unerlaubte Zeichen.'
                                  ])]
            ]);
        } else {
            $builder
                ->add('newPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    //'mapped' => false,
                    'invalid_message' => 'Das Passwort wurde nicht korrekt wiederholt.',
                    'first_options' => [
                        'label' => 'Passwort ändern',
                        'attr' => [
                            'autocomplete' => 'new-password'
                        ],
                        'always_empty' => false,
                        'help' => 'Das Passwort muss aus mindestens 8 Zeichen bestehen und mindestens eine Zahl und einen Buchstaben enthalten. Erlaubte Sonderzeichen: !@#$%._-'
                    ],
                    'second_options' => [
                        'label' => 'Passwort wiederholen',
                        'always_empty' => false
                    ],
                    'constraints' => [new Regex([
                                          'pattern' => '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%._-]{8,}$/Uism',
                                          'message' => 'Das Passwort erfüllt nicht die Mindestanforderungen oder enthält unerlaubte Zeichen.'
                                      ])]
                ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user' => new User()
        ]);
    }

}