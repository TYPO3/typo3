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

namespace TYPO3\CMS\Install\ExtensionScanner\Php\Matcher;

use PhpParser\Node;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Find usages of defined methods within a class that are deprecated/removed.
 * Requires to extend a TYPO3 API class/abstract.
 * This is a strong match.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class AbstractMethodImplementationMatcher extends AbstractCoreMatcher
{
    protected array $matcherDefinitionLookup = [];
    protected const DEFINITION_STATIC = 'static';
    protected const DEFINITION_LOCAL  = 'local';

    /**
     * @param array $matcherDefinitions Incoming main configuration
     */
    public function __construct(array $matcherDefinitions)
    {
        $this->matcherDefinitions = $matcherDefinitions;
        $this->validateMatcherDefinitions();
        $this->initializeFlatMatcherDefinitions();

        //  initializeFlatMatcherDefinitions() unfortunately does not deliver the actual
        //  method, so we need to do something 99% similar here for a custom
        //  property, to not require larger changes to the underlying abstract method.
        foreach ($this->matcherDefinitions as $classAndMethod => $details) {
            $parts = GeneralUtility::trimExplode('::', $classAndMethod);
            $definition = self::DEFINITION_STATIC;
            if (count($parts) !== 2) {
                $parts = GeneralUtility::trimExplode('->', $classAndMethod);
                $definition = self::DEFINITION_LOCAL;
            }
            // Exception-Handling removed, covered by initializeFlatMatcherDefinitions();

            $method = $parts[1];
            $class  = $parts[0];
            if (!array_key_exists($class, $this->matcherDefinitionLookup)) {
                $this->matcherDefinitionLookup[$class][$definition][$method]['candidates'] = [];
            }
            $this->matcherDefinitionLookup[$class][$definition][$method]['candidates'][] = $details;

            // Builds something like:
            // [
            //    'TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper' => [
            //        'static' => [
            //            'renderStatic' => [
            //                'candidates' => [
            //                    [
            //                        'restFiles' => [
            //                            'Deprecation-104789-RenderStaticForFluidViewHelpers.rst',
            //                        ],
            //                    ],
            //                ],
            //            ]
            //        ],
            //    ],
            //    'TYPO3\CMS\AbstractSomething' => [
            //        'local' => [
            //            'someMethodName' => [
            //                'candidates' => [
            //                    [
            //                        'restFiles' => [
            //                            'Breaking-12345-something.rst',
            //                        ],
            //                    ],
            //                    [
            //                        'restFiles' => [
            //                            'Breaking-67890-something.rst',
            //                        ],
            //                    ],
            //                ],
            //            ]
            //        ],
            //    ],
            // ];
        }
    }

    /**
     * Called by PhpParser.
     * Test for a defined method that shall longer be utilized (strong match)
     */
    public function enterNode(Node $node): null
    {
        if (!$this->isFileIgnored($node)
            && !$this->isLineIgnored($node)
            && $node instanceof Node\Stmt\Class_
            && $node->extends) {

            // We found a class definition.
            // Check what classes this definition is extending (Abstract).
            // Without a class extending something, this is not API usage and thus not scanned.
            // Now check if the extended class is part of our matcherDefinition to inspect
            if (array_key_exists($node->extends->name, $this->matcherDefinitionLookup)) {

                // Iterate all declared methods (of the inspected custom class, NOT the abstract!)
                $lookupMethods = $this->matcherDefinitionLookup[$node->extends->name];
                foreach ($node->getMethods() as $method) {

                    // The matcherDefinition can utilize 'Abstract::staticMethod' or 'Abstract->localMethod',
                    // which is handled distinctly, so that the matches are stronger.
                    $lookupKey = $method->isStatic() ? self::DEFINITION_STATIC : self::DEFINITION_LOCAL;

                    if (isset($lookupMethods[$lookupKey][$method->name->toString()]['candidates'])) {
                        // The checked method of an object extending a deprecated/BC class was a match.
                        // Gather final match info (multiple ReST files can apply to a single class+method)
                        foreach ($lookupMethods[$lookupKey][$method->name->toString()]['candidates'] as $candidate) {
                            $this->matches[] = [
                                'restFiles' => $candidate['restFiles'],
                                'line' => $method->getAttribute('startLine'),
                                'message' => sprintf(
                                    'Definition of %s method "%s" extends from "%s"',
                                    $lookupKey,
                                    $method->name->toString(),
                                    $node->extends->name
                                ),
                                'indicator' => 'strong',
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }
}
