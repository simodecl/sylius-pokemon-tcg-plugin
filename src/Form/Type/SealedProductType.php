<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Form\Type;

use SimoDecl\SyliusPokemonTcgPlugin\Service\SealedProductCreator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SealedProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'simo_decl_pokemon_tcg.form.sealed_product.name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 3, max: 255),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'simo_decl_pokemon_tcg.form.sealed_product.type',
                'choices' => array_flip(SealedProductCreator::PRODUCT_TYPES),
                'placeholder' => 'Select a product type...',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('set_id', ChoiceType::class, [
                'label' => 'simo_decl_pokemon_tcg.form.sealed_product.set',
                'required' => false,
                'choices' => $options['set_choices'],
                'placeholder' => 'Select a set (optional)...',
            ])
            ->add('price', NumberType::class, [
                'label' => 'simo_decl_pokemon_tcg.form.sealed_product.price',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Price in your base currency (e.g., 4.99)',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'simo_decl_pokemon_tcg.form.sealed_product.description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'set_choices' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'simo_decl_pokemon_tcg_sealed_product';
    }
}
