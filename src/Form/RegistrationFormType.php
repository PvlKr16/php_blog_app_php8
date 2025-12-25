<?php

namespace App\Form;

use App\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите имя пользователя']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Имя пользователя должно быть не менее {{ limit }} символов',
                        'max' => 50,
                    ]),
                ],
                'label' => 'Имя пользователя',
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите email']),
                    new Email(['message' => 'Введите корректный email']),
                ],
                'label' => 'Email',
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Аватар (необязательно)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Файл слишком большой ({{ size }} {{ suffix }}). Максимум {{ limit }} {{ suffix }}.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Загрузите изображение в формате JPG, PNG, GIF или WEBP',
                        'minWidth' => 50,
                        'minHeight' => 50,
                        'minWidthMessage' => 'Изображение слишком маленькое ({{ width }}px). Минимум {{ min_width }}px.',
                        'minHeightMessage' => 'Изображение слишком маленькое ({{ height }}px). Минимум {{ min_height }}px.',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/jpg,image/png,image/gif,image/webp',
                ],
                'help' => 'JPG, PNG, GIF, WEBP. Макс. 5 МБ. Рекомендуется 200x200px - 1000x1000px',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Пароли должны совпадать',
                'first_options'  => ['label' => 'Пароль'],
                'second_options' => ['label' => 'Повторите пароль'],
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите пароль']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен быть не менее {{ limit }} символов',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
