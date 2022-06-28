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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/**
 * Several functions related to naming and conversions of names
 * such as translation between Repository and Model names or
 * exploding an objectControllerName into pieces
 */
class ClassNamingUtility
{
    /**
     * Translates a model name to an appropriate repository name
     * e.g. Tx_Extbase_Domain_Model_Foo to Tx_Extbase_Domain_Repository_FooRepository
     * or \TYPO3\CMS\Extbase\Domain\Model\Foo to \TYPO3\CMS\Extbase\Domain\Repository\FooRepository
     */
    public static function translateModelNameToRepositoryName(string $modelName): string
    {
        return str_replace(
            '\\Domain\\Model',
            '\\Domain\\Repository',
            $modelName
        ) . 'Repository';
    }

    /**
     * Translates a repository name to an appropriate model name
     * e.g. Tx_Extbase_Domain_Repository_FooRepository to Tx_Extbase_Domain_Model_Foo
     * or \TYPO3\CMS\Extbase\Domain\Repository\FooRepository to \TYPO3\CMS\Extbase\Domain\Model\Foo
     *
     * @param class-string<RepositoryInterface> $repositoryName
     *
     * @return class-string
     */
    public static function translateRepositoryNameToModelName(string $repositoryName): string
    {
        return preg_replace(
            ['/\\\\Domain\\\\Repository/', '/Repository$/'],
            ['\\Domain\\Model', ''],
            $repositoryName
        );
    }

    /**
     * Explodes a controllerObjectName like \Vendor\Ext\Controller\FooController
     * into several pieces like vendorName, extensionName, subpackageKey and controllerName
     *
     * @param string $controllerObjectName The controller name to be exploded
     * @return array<string> An array of controllerObjectName pieces
     */
    public static function explodeObjectControllerName(string $controllerObjectName): array
    {
        $matches = [];
        $extensionName = str_starts_with($controllerObjectName, 'TYPO3\\CMS')
            ? '^(?P<vendorName>[^\\\\]+\\\[^\\\\]+)\\\(?P<extensionName>[^\\\\]+)'
            : '^(?P<vendorName>[^\\\\]+)\\\\(?P<extensionName>[^\\\\]+)';
        preg_match(
            '/' . $extensionName . '\\\\(Controller|Command|(?P<subpackageKey>.+)\\\\Controller)\\\\(?P<controllerName>[a-z\\\\]+)Controller$/ix',
            $controllerObjectName,
            $matches
        );
        return array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);
    }
}
