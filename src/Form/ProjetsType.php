<?php

namespace App\Form;

use App\Entity\Projets;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProjetsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom' , TextType::class,['label'=>'Nom','required'=>false
            ,'attr'=>[
                'class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'min' => 0]
            ])
            ->add('description' , TextType::class,['label'=>'Description','required'=>false
            ,'attr'=>[
                'class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'min' => 0]
            ])
            ->add('logos',FileType::class,[
                'label'=>'Logo',
                'required'=>false,
                'mapped'=>false,
                'attr'=>[
                    'class' => 'form-w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition-colors', 'min' => 0]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projets::class,
        ]);
    }
}
