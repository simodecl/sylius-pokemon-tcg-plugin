<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $pokemonTcgMenu = $menu
            ->addChild('pokemon_tcg')
            ->setLabel('simo_decl_pokemon_tcg.ui.menu.pokemon_tcg')
        ;

        $pokemonTcgMenu
            ->addChild('pokemon_tcg_dashboard', [
                'route' => 'simo_decl_pokemon_tcg_admin_dashboard',
            ])
            ->setLabel('simo_decl_pokemon_tcg.ui.menu.dashboard')
            ->setLabelAttribute('icon', 'dashboard')
        ;

        $pokemonTcgMenu
            ->addChild('pokemon_tcg_import', [
                'route' => 'simo_decl_pokemon_tcg_admin_import_taxonomies',
            ])
            ->setLabel('simo_decl_pokemon_tcg.ui.menu.import_taxonomies')
            ->setLabelAttribute('icon', 'download')
        ;

        $pokemonTcgMenu
            ->addChild('pokemon_tcg_card_search', [
                'route' => 'simo_decl_pokemon_tcg_admin_card_search',
            ])
            ->setLabel('simo_decl_pokemon_tcg.ui.menu.card_search')
            ->setLabelAttribute('icon', 'search')
        ;

        $pokemonTcgMenu
            ->addChild('pokemon_tcg_sealed', [
                'route' => 'simo_decl_pokemon_tcg_admin_sealed_product_create',
            ])
            ->setLabel('simo_decl_pokemon_tcg.ui.menu.sealed_products')
            ->setLabelAttribute('icon', 'box')
        ;
    }
}
