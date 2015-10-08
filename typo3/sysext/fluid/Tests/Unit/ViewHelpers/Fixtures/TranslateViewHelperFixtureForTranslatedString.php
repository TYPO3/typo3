<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures;

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

use TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper;

/**
 * Fixture class for mocking static translate function
 */
class TranslateViewHelperFixtureForTranslatedString extends TranslateViewHelper
{
    /**
     * In original class this is wrapper call to static LocalizationUtility
     *
     * @param string $id Translation Key compatible to TYPO3 Flow
     * @param string $extensionName UpperCamelCased extension key (for example BlogExample)
     * @param array $arguments Arguments to be replaced in the resulting string
     *
     * @return NULL|string
     */
    protected static function translate($id, $extensionName, $arguments)
    {
        return '<p>hello world</p>';
    }
}
