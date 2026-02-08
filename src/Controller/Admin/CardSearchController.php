<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Controller\Admin;

use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use SimoDecl\SyliusPokemonTcgPlugin\Service\CardProductCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CardSearchController extends AbstractController
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
        private readonly CardProductCreator $cardProductCreator,
    ) {
    }

    /**
     * Show the card search page.
     */
    public function searchAction(Request $request): Response
    {
        $query = $request->query->getString('q', '');
        $setId = $request->query->getString('set', '');
        $cards = [];

        if ($query !== '') {
            $cards = $this->tcgdexClient->searchCards($query);
        } elseif ($setId !== '') {
            $setData = $this->tcgdexClient->fetchSet($setId);
            $cards = $setData['cards'] ?? [];
        }

        // Fetch sets for the filter dropdown
        $sets = $this->tcgdexClient->fetchSets();

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/card_search.html.twig', [
            'cards' => $cards,
            'sets' => $sets,
            'query' => $query,
            'selectedSet' => $setId,
        ]);
    }

    /**
     * View a single card's details from the API.
     */
    public function viewCardAction(string $cardId): Response
    {
        $cardData = $this->tcgdexClient->fetchCard($cardId);
        if ($cardData === null) {
            $this->addFlash('error', sprintf('Card "%s" not found.', $cardId));

            return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_card_search');
        }

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/card_view.html.twig', [
            'card' => $cardData,
        ]);
    }

    /**
     * Create a product from a card.
     */
    public function createProductFromCardAction(Request $request, string $cardId): Response
    {
        $defaultPrice = $request->request->getInt('default_price', 0);
        $priceCents = $defaultPrice > 0 ? $defaultPrice : null;

        try {
            $product = $this->cardProductCreator->createFromCard($cardId, $priceCents);
            $this->addFlash('success', sprintf(
                'Product "%s" created successfully.',
                $product->getName(),
            ));

            return $this->redirectToRoute('sylius_admin_product_update', [
                'id' => $product->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Failed to create product: %s', $e->getMessage()));

            return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_card_view', [
                'cardId' => $cardId,
            ]);
        }
    }
}
