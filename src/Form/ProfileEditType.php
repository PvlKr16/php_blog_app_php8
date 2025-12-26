<?php

namespace App\Form;

use App\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Имя пользователя',
                'attr' => ['class' => 'form-control'],
                'disabled' => true,
                'help' => 'Имя пользователя изменить нельзя',
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'disabled' => true,
                'help' => 'Email изменить нельзя',
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Изменить аватар',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Файл слишком большой. Максимум 5 МБ.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Загрузите изображение в формате JPG, PNG, GIF или WEBP',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'JPG, PNG, GIF, WEBP. Макс. 5 МБ',
            ])
            ->add('birthDate', BirthdayType::class, [
                'label' => 'Дата рождения',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Необязательно',
            ])
            ->add('address', TextType::class, [
                'label' => 'Адрес',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Город, страна',
                ],
                'help' => 'Необязательно',
            ])
            ->add('about', TextareaType::class, [
                'label' => 'О себе',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Расскажите немного о себе...',
                ],
                'help' => 'Необязательно',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}