<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Controller\Admin;

use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
    ) {
    }

    public function indexAction(): Response
    {
        return $this->render('@SimoDeclSyliusPokemonTcgPlugin/admin/pokemon_tcg/index.html.twig');
    }
}
