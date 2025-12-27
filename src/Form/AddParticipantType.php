<?php

namespace App\Form;

use App\Document\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', DocumentType::class, [
                'class' => User::class,
                'choice_label' => function(?User $user) {
                    return $user ? $user->getUsername() . ' (' . $user->getEmail() . ')' : '';
                },
                'label' => 'Выберите пользователя',
                'placeholder' => 'Выберите пользователя для добавления',
                'constraints' => [
                    new NotBlank(['message' => 'Пожалуйста, выберите пользователя']),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'existing_participants' => [],
        ]);
    }
}