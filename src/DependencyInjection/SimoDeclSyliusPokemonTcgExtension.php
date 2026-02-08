<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SimoDeclSyliusPokemonTcgExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('simo_decl_pokemon_tcg.api_language', $config['api_language']);
        $container->setParameter('simo_decl_pokemon_tcg.card_languages', $config['card_languages']);
        $container->setParameter('simo_decl_pokemon_tcg.root_taxon_code', $config['root_taxon_code']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }
}
