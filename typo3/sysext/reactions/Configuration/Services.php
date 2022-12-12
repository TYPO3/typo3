<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Reactions\Reaction\ReactionInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder
        ->registerForAutoconfiguration(ReactionInterface::class)
        ->setPublic(true)
        ->setLazy(true)
        ->addTag('reactions.reaction');
};
