<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("city", TextType::class, [
                "label" => "Enter a Finnish city",
                "attr" => ["class" => "form-control"],
                "required" => true,
            ])
            ->add("city2", TextType::class, [
                "label" => "Enter a Finnish city",
                "attr" => ["class" => "form-control"],
                "required" => true,
            ])
            ->add("submit", SubmitType::class, [
                "label" => "Get Daylight Data",
                "attr" => ["class" => "btn btn-primary"],
            ]);
    }
}