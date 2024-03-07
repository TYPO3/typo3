<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\DependencyInjection;

use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use TYPO3\CMS\Resources\Controller\ResourceController;
use TYPO3\CMS\Resources\Definition\Metadata\ScopeInterface;

/**
 * This is a copy of \Sylius\Bundle\ResourceBundle\DependencyInjection\Configuration
 * It should follow the original one closely, so an integration stays feasible.
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('typo3_resources');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('definitions')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('group')->cannotBeEmpty()->end()
                            ->arrayNode('names')
                                ->isRequired()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('plural')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('singular')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('kind')->isRequired()->cannotBeEmpty()->end()
                                    ->arrayNode('shortnames')->scalarPrototype()->end()->end()
                                ->end()
                            ->end()
                            ->scalarNode('scope')->defaultValue(ScopeInterface::SCOPE_GLOBAL)->end()
                            ->arrayNode('versions')
                                ->isRequired()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                        ->booleanNode('served')->defaultTrue()->end()
                                        ->scalarNode('controller')->defaultValue(ResourceController::class)->end()
                                        ->arrayNode('model')
                                            ->children()
                                                ->scalarNode('class')->isRequired()->end()
                                                ->scalarNode('repository')->isRequired()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
