<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;

/**
 * Class DsgvoType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class DsgvoType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('dsgvo', CheckboxType::class,
                [
                    'label' => 'Ich bin damit einverstanden, dass oben stehende Daten entsprechend der <a href="/datenschutz" target="_blank">Datenschutzerklärung</a> in einem automatisierten Verfahren erhoben, gespeichert und verarbeitet werden.',
                    'label_html' => true,
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen']),
                    ]
                ]
            );;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }

}