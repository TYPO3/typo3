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

namespace TYPO3\CMS\Seo\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;

/**
 * Removes the sitemap data provider tag from providers based on the deprecated
 * AbstractXmlSitemapDataProvider. Those receive their runtime information as constructor arguments,
 * which the container can not autowire. They are instantiated by XmlSitemapFactory instead.
 *
 * @internal
 * @deprecated since TYPO3 v15.0, will be removed in TYPO3 v16.0 together with
 *             AbstractXmlSitemapDataProvider.
 */
final readonly class XmlSitemapDataProviderPass implements CompilerPassInterface
{
    public function __construct(private string $tagName) {}

    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds($this->tagName)) as $serviceName) {
            $definition = $container->findDefinition($serviceName);
            $className = $definition->getClass() ?? $serviceName;
            if (is_subclass_of($className, AbstractXmlSitemapDataProvider::class)) {
                $definition->clearTag($this->tagName);
            }
        }
    }
}
