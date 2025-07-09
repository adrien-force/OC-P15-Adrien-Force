<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<User>
 */
class GuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom de l\'invité'],
            ]
        )
        ->add(
            'email',
            EmailType::class,
            [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Entrez l\'email de l\'invité'],
            ]
        )
        ->add(
            'description',
            TextareaType::class,
            [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Entrez une description (optionnel)'],
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);
    }
}