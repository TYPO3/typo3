<?php

declare(strict_types=1);
namespace TYPO3\CMS\Form;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\DependencyInjection\PublicServicePass;
use TYPO3\CMS\Form\Domain\Finishers\FinisherInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(FinisherInterface::class)->addTag('form.finisher');
    $containerBuilder->addCompilerPass(new PublicServicePass('form.finisher', true));
};
