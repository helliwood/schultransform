<?php

namespace Trollfjord\Bundle\CookieBundle\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\CookieBundle\Entity\CookieMain;
use Trollfjord\Bundle\MediaBaseBundle\Component\Form\Type\MediaType;


class CookieMainForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name Cookie-Banner',
            ])
            ->add('title', TextType::class, ['label' => 'Titel Cookie-Banner'])
            ->add('cookieDuration', NumberType::class, ['label' => 'Laufzeit des Cookies(Tage)'])
            ->add('content', TextareaType::class, ['label' => 'Text Cookie-Banner'])
            ->add('privacylinktext', TextType::class, ['label' => 'Bezeichnung Datenschutzlink'])
            ->add('privacylinkpage', TextType::class, ['label' => 'Link auf Datenschutzseite'])
            ->add('btnmainsettings', TextType::class, ['label' => 'Bezeichnung Button "Auswahl ändern"'])
            ->add('btnmainall', TextType::class, ['label' => 'Bezeichnung Button "Alle akzeptieren"'])
            //label group
            ->add('btnsubsettings', TextType::class, ['label' => 'Bezeichnung Button "Auswahl übernehmen"'])
            ->add('btnsuball', TextType::class, ['label' => 'Bezeichnung Button "Alle akzeptieren"'])
            ->add('titlesub', TextType::class, ['label' => 'Titel'])
            ->add('contentsub', TextareaType::class, ['label' => 'Text'])
            ->add('save', SubmitType::class, [
                'label' => 'Speichern',
                'attr' => ['class' => 'btn btn-primary w-50 float-right']
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CookieMain::class
        ]);
    }
}
