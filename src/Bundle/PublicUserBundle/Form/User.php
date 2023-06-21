<?php

namespace Trollfjord\Bundle\PublicUserBundle\Form;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class User extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class)
            ->add('username', TextType::class, [
                'label' => _("Username:"),
                'required' => false
            ])
            ->add('email', TextType::class, [
                'label' => _("E-Mail:"),
                'required' => false,
            ])
            ->add('password', PasswordType::class, [
                'label' => _("Passwort:"),
                'required' => false
            ])
            ->add('save', SubmitType::class)
        ;
    }
}