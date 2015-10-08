<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\Fixtures;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Enter description here...
 */
class PostParseFacetViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\PostParseInterface
{
    public static $wasCalled = false;

    public function __construct()
    {
    }

    public static function postParseEvent(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $viewHelperNode, array $arguments, \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer)
    {
        self::$wasCalled = true;
    }

    public function initializeArguments()
    {
    }

    public function render()
    {
    }
}
