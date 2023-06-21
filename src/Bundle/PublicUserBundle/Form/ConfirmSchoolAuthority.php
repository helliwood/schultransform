<?php

namespace Trollfjord\Bundle\PublicUserBundle\Form;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfirmSchoolAuthority extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('confirmed', CheckboxType::class, [
                'label' => _("Schulträger wurde bestätigt:"),
                'required' => false,
            ])->add('send_mail', ChoiceType::class, array( 'label' => "Bestätigung E-Mail senden?",
                'mapped' => false,
                'choices'  => array(
                    'No' => false,
                    'Yes' => true
                )))

            ->add('save', SubmitType::class)
        ;
    }
}