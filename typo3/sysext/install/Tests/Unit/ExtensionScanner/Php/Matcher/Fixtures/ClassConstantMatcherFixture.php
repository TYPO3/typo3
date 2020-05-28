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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;

/**
 * Fixture file
 */
class ClassConstantMatcherFixture
{
    public function aMethod()
    {
        // Matches
        $foo = \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_FE;
        $foo = SystemEnvironmentBuilder::REQUESTTYPE_FE;
        $foo = \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT;

        // No match
        $foo = \My\Project\AClass::MY_CONSTANT;
        $foo = \My\Different\SystemEnvironmentBuilder::REQUESTTYPE_FE;
        // @extensionScannerIgnoreLine
        $foo = SystemEnvironmentBuilder::REQUESTTYPE_FE;
    }
}
