<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('simo_decl_sylius_pokemon_tcg');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_language')
                    ->defaultValue('en')
                    ->info('Default language for TCGdex API queries (en, fr, es, de, it, pt)')
                ->end()
                ->arrayNode('card_languages')
                    ->info('Available card print languages for product variants')
                    ->defaultValue(['en'])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('root_taxon_code')
                    ->defaultValue('pokemon-tcg')
                    ->info('Code for the root Pokemon TCG taxon')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
