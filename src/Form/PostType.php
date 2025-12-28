<?php

namespace App\Form;

use App\Document\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок записи',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите заголовок']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Заголовок должен быть не менее {{ limit }} символов',
                        'max' => 255,
                        'maxMessage' => 'Заголовок должен быть не более {{ limit }} символов',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Содержимое записи',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, введите содержимое']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Содержимое должно быть не менее {{ limit }} символов',
                    ]),
                ],
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
                                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg',
                                'application/pdf', 'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain', 'text/markdown',
                            ],
                            'mimeTypesMessage' => 'Разрешены только: изображения (JPG, PNG, GIF, WEBP), аудио (MP3, WAV, OGG), документы (PDF, DOC, DOCX, TXT, MD)',
                        ])
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*,audio/*,.pdf,.doc,.docx,.txt,.md',
                ],
                'help' => 'Изображения, аудио, документы. До 5 файлов, макс. 20 МБ каждый.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}