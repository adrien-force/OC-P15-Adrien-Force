<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<Media>
 */
class MediaType extends AbstractType
{
    private const MAX_FILE_SIZE = '2048k';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tiff', 'heic'];
    private const ALLOWED_MIME_TYPES =  ['image/jpg', 'image/jpeg', 'image/png', 'image/webp', 'image/bmp', 'image/tiff', 'image/heic'];


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Image',
                'constraints' => [
                    new File(
                        maxSize: self::MAX_FILE_SIZE,
                        mimeTypes: self::ALLOWED_MIME_TYPES,
                        mimeTypesMessage: sprintf('Les extensions autorisées sont : %s.', implode(', ', self::ALLOWED_EXTENSIONS)),
                        extensions: self::ALLOWED_EXTENSIONS,
                        extensionsMessage: sprintf('Les extensions autorisées sont : %s.', implode(', ', self::ALLOWED_EXTENSIONS))
                    ),
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
        ;

        if ($options['is_admin']) {
            $builder
                ->add('user', EntityType::class, [
                    'label' => 'Utilisateur',
                    'required' => false,
                    'class' => User::class,
                    'choice_label' => 'name',
                ])
                ->add('album', EntityType::class, [
                    'label' => 'Album',
                    'required' => false,
                    'class' => Album::class,
                    'choice_label' => 'name',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_admin' => false,
        ]);
    }
}
