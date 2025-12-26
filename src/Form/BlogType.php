<?php

namespace App\Form;

use App\Document\Blog;
use App\Document\Category;
use App\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите заголовок']),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'Заголовок должен быть не менее {{ limit }} символов',
                        'max' => 255,
                    ]),
                ],
                'label' => 'Заголовок',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => $options['categories'],
                'choice_label' => function(?Category $category) {
                    return $category ? $category->getName() : '';
                },
                'choice_value' => function(?Category $category) {
                    return $category ? $category->getId() : '';
                },
                'label' => 'Тема',
                'placeholder' => 'Выберите тему',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите тему']),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Общий (виден всем авторизованным)' => 'public',
                    'Закрытый (только для участников)' => 'private',
                ],
                'label' => 'Статус блога',
                'data' => 'public',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('participants', DocumentType::class, [
                'class' => User::class,
                'choice_label' => function(?User $user) {
                    return $user ? $user->getUsername() . ' (' . $user->getEmail() . ')' : '';
                },
                'label' => 'Участники (необязательно)',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'size' => 5,
                ],
                'help' => 'Удерживайте Ctrl (Cmd на Mac) для выбора нескольких пользователей. Вы автоматически добавляетесь как участник.',
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите содержимое']),
                    new Length([
                        'min' => 20,
                        'minMessage' => 'Содержимое должно быть не менее {{ limit }} символов',
                    ]),
                ],
                'label' => 'Содержимое',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
            'categories' => [],
        ]);

        $resolver->setAllowedTypes('categories', 'array');
    }
}
