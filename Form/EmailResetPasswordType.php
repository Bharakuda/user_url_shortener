<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('save', SubmitType::class, array(
                'label' => 'Submit',
                'attr' => array('class' => 'button')
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // todo: provjeri ako je potrebno nesto u ovim opcijama
//        $resolver->setDefaults(array(
//            'data_class' => 'UserBundle\Entity\PasswordResetToken'
//        ));
    }

    public function getName()
    {
        return 'user_bundle_email_reset_password_type';
    }
}
