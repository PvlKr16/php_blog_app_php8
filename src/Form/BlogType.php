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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

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
            ])
            ->add('attachments', FileType::class, [
                'label' => 'Прикрепить файлы (необязательно)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new Count([
                        'max' => 5,
                        'maxMessage' => 'Можно загрузить максимум {{ limit }} файлов',
                    ]),
                    new All([
                        new File([
                            'maxSize' => '20M',
                            'maxSizeMessage' => 'Файл слишком большой ({{ size }} {{ suffix }}). Максимум {{ limit }} {{ suffix }}.',
                            'mimeTypes' => [
                                // Изображения
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                // Аудио
                                'audio/mpeg',
                                'audio/mp3',
                                'audio/wav',
                                'audio/ogg',
                                // Документы
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain',
                                'text/markdown',
                            ],
                            'mimeTypesMessage' => 'Разрешены только: изображения (JPG, PNG, GIF, WEBP), аудио (MP3, WAV, OGG), документы (PDF, DOC, DOCX, TXT, MD)',
                        ])
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*,audio/*,.pdf,.doc,.docx,.txt,.md',
                ],
                'help' => 'Изображения (JPG, PNG, GIF, WEBP) - макс. 10 МБ. Аудио (MP3, WAV, OGG) - макс. 20 МБ. Документы (PDF, DOC, DOCX, TXT, MD) - макс. 10 МБ. До 5 файлов.',
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
