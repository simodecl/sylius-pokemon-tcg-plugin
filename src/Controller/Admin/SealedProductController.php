<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Controller\Admin;

use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use SimoDecl\SyliusPokemonTcgPlugin\Form\Type\SealedProductType;
use SimoDecl\SyliusPokemonTcgPlugin\Service\SealedProductCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SealedProductController extends AbstractController
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
        private readonly SealedProductCreator $sealedProductCreator,
    ) {
    }

    public function createAction(Request $request): Response
    {
        $sets = $this->tcgdexClient->fetchSets();

        $setChoices = [];
        foreach ($sets as $set) {
            $setChoices[$set->name] = $set->id;
        }

        $form = $this->createForm(SealedProductType::class, null, [
            'set_choices' => $setChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $product = $this->sealedProductCreator->create(
                    name: $data['name'],
                    type: $data['type'],
                    setId: $data['set_id'] ?: null,
                    priceCents: $data['price'] ? (int) ($data['price'] * 100) : null,
                    description: $data['description'] ?: null,
                );

                $this->addFlash('success', sprintf(
                    'Sealed product "%s" created successfully.',
                    $product->getName(),
                ));

                return $this->redirectToRoute('sylius_admin_product_update', [
                    'id' => $product->getId(),
                ]);
            } catch (\Throwable $e) {
                $this->addFlash('error', sprintf('Failed to create product: %s', $e->getMessage()));
            }
        }

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/sealed_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
