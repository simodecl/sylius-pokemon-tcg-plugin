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

    public function searchAction(Request $request): Response
    {
        $query = $request->query->getString('q', '');
        $setId = $request->query->getString('set', '');
        $cards = [];

        if ($query !== '') {
            $cards = $this->tcgdexClient->searchCards($query);
        } elseif ($setId !== '') {
            $setData = $this->tcgdexClient->fetchSet($setId);
            $cards = $setData !== null ? $setData->cards : [];
        }

        $sets = $this->tcgdexClient->fetchSets();

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/card_search.html.twig', [
            'cards' => $cards,
            'sets' => $sets,
            'query' => $query,
            'selectedSet' => $setId,
        ]);
    }

    public function viewCardAction(string $cardId): Response
    {
        $cardData = $this->tcgdexClient->fetchCard($cardId);
        if ($cardData === null) {
            $this->addFlash('error', sprintf('Card "%s" not found.', $cardId));

            return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_card_search');
        }

        $rawData = $this->tcgdexClient->fetchCardRawData($cardId);
        $pricing = [];
        if ($rawData !== null && isset($rawData['pricing'])) {
            // Recursively convert stdClass to arrays
            $pricing = json_decode(json_encode($rawData['pricing']), true) ?? [];
        }

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/card_view.html.twig', [
            'card' => $cardData,
            'pricing' => $pricing,
        ]);
    }

    public function createProductFromCardAction(Request $request, string $cardId): Response
    {
        $defaultPrice = $request->request->getInt('default_price', 0);
        $priceCents = $defaultPrice > 0 ? $defaultPrice : null;
        $referer = $request->headers->get('referer', '');

        try {
            $product = $this->cardProductCreator->createFromCard($cardId, $priceCents);
            $this->addFlash('success', sprintf(
                'Product "%s" created successfully.',
                $product->getName(),
            ));

            // If created from search page, redirect back to search
            if (str_contains($referer, 'cards') && !str_contains($referer, $cardId)) {
                return $this->redirect($referer);
            }

            return $this->redirectToRoute('sylius_admin_product_update', [
                'id' => $product->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Failed to create product: %s', $e->getMessage()));

            // If created from search page, redirect back to search
            if (str_contains($referer, 'cards') && !str_contains($referer, $cardId)) {
                return $this->redirect($referer);
            }

            return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_card_view', [
                'cardId' => $cardId,
            ]);
        }
    }
}
