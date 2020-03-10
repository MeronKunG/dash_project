<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('getMonth' ,  ChoiceType::class, [
                'choices' => [
                    'มกราคม' => '01',
                    'กุมภาพันธ์' => '02',
                    'มีนาคม' => '03',
                    'เมษายน' => '04',
                    'พฤษภาคม' => '05',
                    'มิถุนายน' => '06',
                    'กรกฎาคม' => '07',
                    'สิงหาคม' => '08',
                    'กันยายน' => '09',
                    'ตุลาคม' => '10',
                    'พฤษจิกายน' => '11',
                    'ธันวาคม' => '12'
                ],
                'data' => '0'.intval(date("m"))
            ])
            ->add('getYear' ,  ChoiceType::class, [
                'choices' =>
                    $this->getYear(date("Y")+3, date("Y")-3),
                'data' => intval(date("Y"))
            ])
//            ->add('submit' , SubmitType::class, [
//                'attr' => ['class' => 'save'],
//            ])
        ;
    }

//    private function getStartDate($max)
//    {
//        $years = range($max, ($min === 'current' ? date('Y') : $min));
//
//        return array_combine($years, $years);
//    }

    private function getYear($max, $min)
    {
        $years = range($max, ($min === 'current' ? date('Y') : $min));
        return array_combine($years, $years);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
