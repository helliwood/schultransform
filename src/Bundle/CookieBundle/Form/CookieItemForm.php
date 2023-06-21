<?php

namespace Trollfjord\Bundle\CookieBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Trollfjord\Bundle\CookieBundle\Entity\CookieItem;
use Trollfjord\Bundle\CookieBundle\Entity\CookieVariation;
use Trollfjord\Bundle\CookieBundle\Repository\CookieVariationRepository;
use Trollfjord\Bundle\CookieBundle\Service\DbTransactions;


class CookieItemForm extends AbstractType
{
    private DbTransactions $dbTransactions;

    /**
     * @param DbTransactions $dbTransactions ;
     */
    public function __construct(DbTransactions $dbTransactions)
    {
        $this->dbTransactions = $dbTransactions;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $idItem = 0;
        $idCookieMain = 0;
        if ($builder->getData()) {
            if ($builder->getData()->getId()) {
                $idItem = $builder->getData()->getId();
            }
            if ($builder->getData()->getCookieMain()) {
                $idCookieMain = $builder->getData()->getCookieMain()->getId();
            }
        }


        $builder
            ->add('name', TextType::class, ['label' => 'Art des Cookies'])
            ->add('necessary', CheckboxType::class,
                [
                    'label' => 'Cookie ist für das Funktionieren der Website erforderlich',
                    'required' => false,
                ])
            ->add('title', TextType::class, ['label' => 'Kurztext eingeklappt'])
            ->add('content', TextareaType::class, ['label' => 'Langtext ausgeklappt', 'required' => false,])
            ->add('icon', TextType::class, ['label' => 'Favicon', 'required' => false,])
            ->add('regex', TextType::class, [
                'label' => 'Extra Regex',
                'required' => false,
            ])
            ->add('position', NumberType::class, [
                'label' => 'Position',
            ])
            ->add('variations', EntityType::class, [
                'label' => 'Regex-Variante',
                'class' => CookieVariation::class,
                'query_builder' => $this->dbTransactions->getNotSelectedOptions($idItem, $idCookieMain),
                'choice_label' => 'title',
                'multiple' => true,
                'expanded' => true,
            ])
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
            'data_class' => CookieItem::class
        ]);
    }
}
