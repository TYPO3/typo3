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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Returns the grouped constraints of an extension
 *
 * @internal
 */
class ConstraintsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('extension', Extension::class, 'extension to process', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): array {
        $groupedConstraints = [];

        foreach ($arguments['extension']->getDependencies() as $dependency) {
            $groupedConstraints[$dependency->getType()][self::getTransformedIdentifier($dependency->getIdentifier())] = [
                'version' => self::getVersionString($dependency->getLowestVersion(), $dependency->getHighestVersion()),
                'versionCompatible' => self::isVersionCompatible($dependency),
            ];
        }

        return $groupedConstraints;
    }

    protected static function getTransformedIdentifier(string $identifier): string
    {
        return in_array($identifier, Dependency::$specialDependencies, true)
            ? strtoupper($identifier)
            : strtolower($identifier);
    }

    protected static function getVersionString(string $lowestVersion, string $highestVersion): string
    {
        $version = '';

        if ($lowestVersion !== '') {
            $version .= '(' . $lowestVersion;
            if ($highestVersion !== '') {
                $version .= ' - ' . $highestVersion;
            }
            $version .= ')';
        }

        return $version;
    }

    protected static function isVersionCompatible(Dependency $dependency): bool
    {
        if ($dependency->getIdentifier() === 'typo3') {
            return $dependency->isVersionCompatible(VersionNumberUtility::getNumericTypo3Version());
        }
        if ($dependency->getIdentifier() === 'php') {
            return $dependency->isVersionCompatible(PHP_VERSION);
        }
        return true;
    }
}
