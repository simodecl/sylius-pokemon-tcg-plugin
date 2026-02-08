<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Controller\Admin;

use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use SimoDecl\SyliusPokemonTcgPlugin\Service\CardProductCreator;
use SimoDecl\SyliusPokemonTcgPlugin\Service\TaxonomyImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImportController extends AbstractController
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
        private readonly TaxonomyImporter $taxonomyImporter,
        private readonly CardProductCreator $cardProductCreator,
    ) {
    }

    /**
     * Show the import taxonomies page with all available series.
     */
    public function taxonomiesAction(): Response
    {
        $series = $this->tcgdexClient->fetchSeries();

        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/import_taxonomies.html.twig', [
            'series' => $series,
        ]);
    }

    /**
     * Import all series and sets as taxons.
     */
    public function importAllTaxonomiesAction(Request $request): Response
    {
        try {
            $result = $this->taxonomyImporter->importAll();
            $this->addFlash('success', sprintf(
                'Successfully imported %d series and %d sets as categories.',
                $result['series'],
                $result['sets'],
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Import failed: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_import_taxonomies');
    }

    /**
     * Import a single series and its sets.
     */
    public function importSerieAction(Request $request, string $serieId): Response
    {
        try {
            $result = $this->taxonomyImporter->importSerie($serieId);
            $this->addFlash('success', sprintf(
                'Successfully imported series "%s" with %d sets.',
                $result['series'],
                $result['sets'],
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Import failed: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_import_taxonomies');
    }

    /**
     * Import all cards from a set as products.
     */
    public function importSetCardsAction(Request $request, string $setId): Response
    {
        $defaultPrice = $request->request->getInt('default_price', 0);
        $priceCents = $defaultPrice > 0 ? $defaultPrice : null;

        try {
            $result = $this->cardProductCreator->createFromSet($setId, $priceCents);
            $this->addFlash('success', sprintf(
                'Created %d card products (%d skipped as already existing).',
                $result['created'],
                $result['skipped'],
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Import failed: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('simo_decl_pokemon_tcg_admin_import_taxonomies');
    }
}
