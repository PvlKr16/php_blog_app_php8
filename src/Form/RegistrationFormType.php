<?php

namespace App\Form;

use App\Document\Department;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Имя пользователя',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите имя пользователя']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Имя пользователя должно быть не менее {{ limit }} символов',
                        'max' => 50,
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите email']),
                    new Email(['message' => 'Пожалуйста, введите корректный email']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Пароль',
                    'attr' => ['class' => 'form-control'],
                ],
                'second_options' => [
                    'label' => 'Повторите пароль',
                    'attr' => ['class' => 'form-control'],
                ],
                'invalid_message' => 'Пароли должны совпадать',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите пароль']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен быть не менее {{ limit }} символов',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('departmentId', TextType::class, [
                'label' => 'Подразделение',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите подразделение']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'display: none;', // Скрываем, используем кастомный select
                ],
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Аватар (необязательно)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Файл слишком большой. Максимум 5 МБ.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Пожалуйста, загрузите изображение в формате JPG, PNG, GIF или WEBP',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
            ]);

        // Обработка подразделения после submit
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();
            $departmentId = $form->get('departmentId')->getData();

            if ($departmentId && $departmentId !== '__new__') {
                $department = $this->dm->getRepository(Department::class)->find($departmentId);
                if ($department) {
                    $user->setDepartment($department);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }
}