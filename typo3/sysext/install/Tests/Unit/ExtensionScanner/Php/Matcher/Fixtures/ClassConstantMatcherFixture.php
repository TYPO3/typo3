<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;

/**
 * Fixture file
 */
class ClassConstantMatcherFixture
{
    public function aMethod()
    {
        // Matches
        $foo = \TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_ERROR;
        $foo = DocumentTemplate::STATUS_ICON_ERROR;
        $foo = \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT;

        // No match
        $foo = \My\Project\AClass::MY_CONSTANT;
        $foo = \My\Different\DocumentTemplate::STATUS_ICON_ERROR;
        // @extensionScannerIgnoreLine
        $foo = DocumentTemplate::STATUS_ICON_ERROR;
    }
}
