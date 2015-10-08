<?php
namespace TYPO3\CMS\Fluid\Compatibility;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/**
 * Build a template parser.
 * Use this class to get a fresh instance of a correctly initialized Fluid template parser.
 */
class TemplateParserBuilder
{
    /**
     * Creates a new TemplateParser which is correctly initialized. This is the correct
     * way to get a Fluid parser instance.
     *
     * @return \TYPO3\CMS\Fluid\Core\Parser\TemplateParser A correctly initialized Template Parser
     */
    public static function build()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $templateParser = $objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class);
        return $templateParser;
    }
}
