<?php

namespace App\Form;

use App\Document\Blog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
        ]);
    }
}
