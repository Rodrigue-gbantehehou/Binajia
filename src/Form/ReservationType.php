<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Nom', TextType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Entrez votre nom']
            ])
            ->add('email', EmailType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'exemple@domaine.com']
            ])
            ->add('telephone', TelType::class, [
                'label' => false,
                'attr' => ['placeholder' => '+229 XX XX XX XX']
            ])
            ->add('paysresidence', CountryType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'Votre pays']
            ])
            ->add('destination', TextType::class, [
                'label' => false,
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'placeholder' => 'Destination du voyage']
            ])
            ->add('datedepart', DateType::class, [
                'label' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors']
            ])
            ->add('nbrvoyageurs', IntegerType::class, [
                'label' => false,
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'min' => 1]
            ])
            ->add('typevoyage', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Loisir' => 'loisir',
                    'Affaires' => 'affaires',
                    'Aventure' => 'aventure',
                    'Autre' => 'autre'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('centreinteret', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'placeholder' => 'Ex: Plage, Culture, Aventure']
            ])
            ->add('budgetestime', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'min' => 0]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'rows' => 4, 'placeholder' => 'Vos remarques...']
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
