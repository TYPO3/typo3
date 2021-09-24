<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Fluid\Core\Rendering;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactoryInterface;
use TYPO3Fluid\Fluid\Core\Cache\FluidCacheInterface;
use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\EscapingModifierTemplateProcessor;
use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\NamespaceDetectionTemplateProcessor;
use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\PassthroughSourceModifierTemplateProcessor;
use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessorInterface;

/**
 * Factory class registered in ServiceProvider to create a RenderingContext.
 *
 * This is a low level factory always registered, even in failsafe mode: fluid
 * is needed in install tool which does not rely on the normal (cached) symfony DI
 * mechanism - Services.yaml is ignored in failsafe mode.
 *
 * A casual failsafe instantiation / injection using ServiceProvider.php wouldn't
 * need this factory. But the failsafe mechanism is strict and relies on two
 * things: The service is public: true, this is the case with RenderingContext.
 * And the service is shared: true - a stateless singleton. This is not true for
 * RenderingContext, it by definition relies on context and must be created a-new
 * per fluid parsing instance.
 *
 * To allow creating RenderingContext objects in failsafe mode, this factory
 * is registered as service provider to dynamically prepare instances.
 *
 * @internal May change / vanish any time
 */
final class RenderingContextFactory
{
    private ContainerInterface $container;
    private CacheManager $cacheManager;
    private ViewHelperResolverFactoryInterface $viewHelperResolverFactory;

    public function __construct(
        ContainerInterface $container,
        CacheManager $cacheManager,
        ViewHelperResolverFactoryInterface $viewHelperResolverFactory
    ) {
        $this->container = $container;
        $this->cacheManager = $cacheManager;
        $this->viewHelperResolverFactory = $viewHelperResolverFactory;
    }

    public function create(): RenderingContext
    {
        /** @var TemplateProcessorInterface[] $processors */
        $processors = [];

        if ($this->container instanceof FailsafeContainer) {
            // Load default set of processors in failsafe mode (install tool context)
            // as custom processors can not be retrieved from the symfony container
            $processors = [
                new EscapingModifierTemplateProcessor(),
                new PassthroughSourceModifierTemplateProcessor(),
                new NamespaceDetectionTemplateProcessor(),
            ];
        } else {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['preProcessors'] as $className) {
                if ($this->container->has($className)) {
                    /** @var TemplateProcessorInterface[] $processors */
                    $processors[] = $this->container->get($className);
                } else {
                    // @deprecated since v11, will be removed with 12.
                    // Layer for processors that can't be instantiated by symfony-DI yet,
                    // probably due to a missing Services.yaml in the providing extension. Fall back to ObjectManager,
                    // which logs a deprecation. If condition and else can be dropped in v12.
                    $objectManager = $this->container->get(ObjectManager::class);
                    /** @var TemplateProcessorInterface[] $processors */
                    $processors[] = $objectManager->get($className);
                }
            }
        }

        $cache = $this->cacheManager->getCache('fluid_template');
        if (!$cache instanceof FluidCacheInterface) {
            throw new \RuntimeException('Cache fluid_template must implement FluidCacheInterface', 1623148753);
        }

        return new RenderingContext(
            $this->viewHelperResolverFactory->create(),
            $cache,
            $processors,
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['expressionNodeTypes']
        );
    }
}
