<?php

namespace Trollfjord\Bundle\PublicUserBundle\Form;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddUser extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => _("Username:"),
                'required' => true
            ])
            ->add('email', TextType::class, [
                'label' => _("E-Mail:"),
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => _("Passwort:"),
                'required' => true
            ])
            ->add('save', SubmitType::class)
        ;
    }
}