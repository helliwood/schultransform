<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Entity\Address;
use function dump;
use function preg_match;

/**
 * Class AddressType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class AddressType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['enable_country']) {
            $builder->add('other_country', CheckboxType::class, ['data' => $options['country_checked'], 'label' => 'Die Adresse liegt nicht in Deutschland.', 'mapped' => false]);
        }
        $builder
            ->add('street')
            ->add('postalcode')
            ->add('city')
            ->add('federalState', ChoiceType::class, [
                'choices' => [
                    'Baden-Württemberg' => 'Baden-Württemberg',
                    'Bayern' => 'Bayern',
                    'Berlin' => 'Berlin',
                    'Brandenburg' => 'Brandenburg',
                    'Bremen' => 'Bremen',
                    'Hamburg' => 'Hamburg',
                    'Hessen' => 'Hessen',
                    'Mecklenburg-Vorpommern' => 'Mecklenburg-Vorpommern',
                    'Niedersachsen' => 'Niedersachsen',
                    'Nordrhein-Westfalen' => 'Nordrhein-Westfalen',
                    'Rheinland-Pfalz' => 'Rheinland-Pfalz',
                    'Saarland' => 'Saarland',
                    'Sachsen' => 'Sachsen',
                    'Sachsen-Anhalt' => 'Sachsen-Anhalt',
                    'Schleswig-Holstein' => 'Schleswig-Holstein',
                    'Thüringen' => 'Thüringen',
                ],
                'placeholder' => '-- Bitte wählen --',
                'row_attr' => ['id' => 'federal-state-row']
            ]);
        if ($options['enable_country']) {
            $builder->add('country', TextType::class, ['label' => 'Land', 'required' => true, 'row_attr' => ['id' => 'country-row']]);
        }
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
            if ($options['enable_country']) {
                if ($event->getForm()->get('other_country')->getData() === true) {
                    // Land muss angegeben werden
                    if (empty($event->getForm()->get('country')->getData())) {
                        $event->getForm()->get('country')->addError(
                            new FormError('Dieser Wert sollte nicht leer sein.')
                        );
                    }
                } else {
                    if (empty($event->getForm()->get('federalState')->getData())) {
                        $event->getForm()->get('federalState')->addError(
                            new FormError('Dieser Wert sollte nicht leer sein.')
                        );
                    }
                    if (! preg_match("/^[0-9]{4,5}$/", $event->getForm()->get('postalcode')->getData())) {
                        $event->getForm()->get('postalcode')->addError(
                            new FormError('Dieser Wert is ungültig.')
                        );
                    }
                }
            } else {
                if (! preg_match("/^[0-9]{4,5}$/", $event->getForm()->get('postalcode')->getData())) {
                    $event->getForm()->get('postalcode')->addError(
                        new FormError('Dieser Wert is ungültig.')
                    );
                }
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'enable_country' => false,
            'country_checked' => false
        ]);
    }

}