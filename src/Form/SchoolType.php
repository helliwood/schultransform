<?php

namespace Trollfjord\Form;

use Doctrine\Persistence\ObjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;
use Trollfjord\Entity\School;
use function is_null;

/**
 * Class SchoolType
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Form
 */
class SchoolType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $country_checked = false;
        if (isset($options["data"]) && ! is_null($options["data"]->getAddress()) && ! is_null($options["data"]->getAddress()->getCountry())) {
            $country_checked = true;
        }
        $builder
            ->add('name', null, ["label" => "schoolname"]);

        if (! isset($options["data"]) || is_null($options["data"]->getId())) {
            $builder->add('schoolType', EntityType::class, [
                'class' => \Trollfjord\Entity\SchoolType::class,
                'placeholder' => '-- Bitte wählen --',
                'query_builder' => function (ObjectRepository $r) {
                    return $r->createQueryBuilder('st')
                        ->orderBy('st.position', 'ASC');
                },
                'required' => false,
                'constraints' => [new NotBlank()]
            ]);
        }

        $builder->add('address', AddressType::class, ["label" => false, 'enable_country' => true, 'country_checked' => $country_checked, 'constraints' => [new Valid()]])
            ->add('phoneNumber')
            ->add('faxNumber')
            ->add('emailAddress')
            ->add('headmaster');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => School::class,
            'cascade_validation' => true,
        ]);
    }

}