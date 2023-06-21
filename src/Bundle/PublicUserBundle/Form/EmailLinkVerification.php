<?php

namespace Trollfjord\Bundle\PublicUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailLinkVerification extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message_to_user', TextareaType::class,
                [
                    'label' => "Nachricht an den Benutzer",
                    'required' => false,
                ])
            ->add('send', SubmitType::class, [
                'label' => 'E-Mail Senden',
            ]);
    }
}