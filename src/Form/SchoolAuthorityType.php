<?php

namespace Trollfjord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Trollfjord\Entity\SchoolAuthority;

/**
 * Class SchoolType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class SchoolAuthorityType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('name', null, ["label" => "School Authority"])
            ->add('address', AddressType::class, ["label" => false, 'enable_country' => true, 'constraints' => [new Valid()]])
            ->add('phoneNumber')
            ->add('faxNumber')
            ->add('emailAddress')
            ->add('contactPerson');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SchoolAuthority::class
        ]);
    }

}