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

namespace TYPO3\CMS\Core\Package;

use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class PseudoServiceProvider extends AbstractServiceProvider
{
    /**
     * @var PackageInterface
     */
    private $package;

    public function __construct(PackageInterface $package)
    {
        $this->package = $package;
    }

    protected static function getPackagePath(): string
    {
        throw new \BadMethodCallException('PseudoServiceProvider does not support the getPackagePath() method.', 1562354465);
    }

    protected static function getPackageName(): string
    {
        throw new \BadMethodCallException('PseudoServiceProvider does not support the getPackageName() method.', 1643372902);
    }

    public function getFactories(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        $packagePath = $this->package->getPackagePath();
        // Fallback to empty string if dealing with an extension in non-composer mode
        // that still does not provide composer.json.
        $packageName = $this->package->getValueFromComposerManifest('name') ?? '';
        $extensions = parent::getExtensions();

        // The static configure*() methods in AbstractServiceProvider use the
        // static getPackagePath() method to retrieve the package path.
        // We can not provide a static package path for pseudo service providers,
        // therefore we dynamically inject the package path to the static service
        // configure methods by wrapping these in a Closure.
        // AbstractServiceProvider configure methods are aware of this and
        // provide an optional third parameter which is forwarded as
        // dynamic path to getPackagePath().
        // Same logic for $packageName.
        foreach ($extensions as $serviceName => $previousCallable) {
            $extensions[$serviceName] = static function (ContainerInterface $container, $value) use ($previousCallable, $packagePath, $packageName) {
                return ($previousCallable)($container, $value, $packagePath, $packageName);
            };
        }

        return $extensions;
    }
}
