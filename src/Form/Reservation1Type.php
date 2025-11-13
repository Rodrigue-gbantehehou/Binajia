<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class Reservation1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom')
            ->add('email')
            ->add('telephone')
            ->add('paysresidence')
            ->add('destination')
            ->add('datedepart', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false, // ← Désactiver html5
                'format' => 'dd/MM/yyyy | HH:mm',
                'data' => new \DateTime(),
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'dd/MM/yyyy | HH:mm',
                ],
            ])
            ->add('nbrvoyageurs')
            ->add('typevoyage')
            ->add('centreinteret')
            ->add('budgetestime')
            ->add('commentaire')

            ->add('typereservation')
            ->add('evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
