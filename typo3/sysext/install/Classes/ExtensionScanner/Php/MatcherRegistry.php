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

namespace TYPO3\CMS\Install\ExtensionScanner\Php;

use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractMethodImplementationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayDimensionMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayGlobalMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassNameMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstructorArgumentMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\FunctionCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\InterfaceMethodChangedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentUnusedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallArgumentValueMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyExistsStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyProtectedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyPublicMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ScalarStringMatcher;

/**
 * Registry of extension scanner matchers: node visitors implementing CodeScannerInterface
 * together with the configuration file declaring what each of them looks for.
 *
 * Not final on purpose: unit tests substitute this to scan against a test configuration
 * instead of the full core rule set.
 *
 * @todo Make the matchers declare themselves via taggable services and locators for a public API
 * @todo When opening up the API for future TYPO3 versions, class should be made final and tests adjusted.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MatcherRegistry
{
    /**
     * The order is significant: it determines the order of matches in the scan result.
     *
     * @return list<array{class: class-string, configurationFile: string}>
     */
    public function getMatcherConfigurations(): array
    {
        return [
            [
                'class' => ArrayDimensionMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayDimensionMatcher.php',
            ],
            [
                'class' => ArrayGlobalMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ArrayGlobalMatcher.php',
            ],
            [
                'class' => ClassConstantMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassConstantMatcher.php',
            ],
            [
                'class' => ClassNameMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ClassNameMatcher.php',
            ],
            [
                'class' => ConstantMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ConstantMatcher.php',
            ],
            [
                'class' => ConstructorArgumentMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ConstructorArgumentMatcher.php',
            ],
            [
                'class' => PropertyAnnotationMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyAnnotationMatcher.php',
            ],
            [
                'class' => MethodAnnotationMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodAnnotationMatcher.php',
            ],
            [
                'class' => FunctionCallMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/FunctionCallMatcher.php',
            ],
            [
                'class' => AbstractMethodImplementationMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/AbstractMethodImplementationMatcher.php',
            ],
            [
                'class' => InterfaceMethodChangedMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/InterfaceMethodChangedMatcher.php',
            ],
            [
                'class' => MethodArgumentDroppedMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedMatcher.php',
            ],
            [
                'class' => MethodArgumentDroppedStaticMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentDroppedStaticMatcher.php',
            ],
            [
                'class' => MethodArgumentRequiredMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentRequiredMatcher.php',
            ],
            [
                'class' => MethodArgumentRequiredStaticMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentRequiredStaticMatcher.php',
            ],
            [
                'class' => MethodArgumentUnusedMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodArgumentUnusedMatcher.php',
            ],
            [
                'class' => MethodCallMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallMatcher.php',
            ],
            [
                'class' => MethodCallArgumentValueMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallArgumentValueMatcher.php',
            ],
            [
                'class' => MethodCallStaticMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/MethodCallStaticMatcher.php',
            ],
            [
                'class' => PropertyExistsStaticMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyExistsStaticMatcher.php',
            ],
            [
                'class' => PropertyProtectedMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyProtectedMatcher.php',
            ],
            [
                'class' => PropertyPublicMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/PropertyPublicMatcher.php',
            ],
            [
                'class' => ScalarStringMatcher::class,
                'configurationFile' => 'EXT:install/Configuration/ExtensionScanner/Php/ScalarStringMatcher.php',
            ],
        ];
    }
}
