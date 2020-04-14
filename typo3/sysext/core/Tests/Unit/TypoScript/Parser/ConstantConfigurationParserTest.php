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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConstantConfigurationParserTest extends UnitTestCase
{
    protected $backupEnvironment = true;

    /**
     * @test
     */
    public function getConfigurationAsValuedArrayReturnsArrayOfConstants(): void
    {
        $rawConfiguration = '
# cat=basic/xxx/011; type=options[de,en,fr,zh]; label= options[de, en, fr, zh] a drop down option field
t7 = de

# cat=basic/xxx/010; type=boolean; label= enable: a checkbox
t8 = 1

# cat=basic/xxx/010; type=small; label= small a short input field
t9 = xyz';

        $expected = [
            't7' => [
                'cat' => 'basic',
                'subcat' => 'x/011z',
                'type' => 'options[de,en,fr,zh]',
                'label' => 'options[de, en, fr, zh] a drop down option field',
                'name' => 't7',
                'value' => 'de',
                'default_value' => 'de',
            ],
            't8' => [
                'cat' => 'basic',
                'subcat' => 'x/010z',
                'type' => 'boolean',
                'label' => 'enable: a checkbox',
                'name' => 't8',
                'value' => '1',
                'default_value' => '1',
            ],
            't9' => [
                'cat' => 'basic',
                'subcat' => 'x/010z',
                'type' => 'small',
                'label' => 'small a short input field',
                'name' => 't9',
                'value' => 'xyz',
                'default_value' => 'xyz',
            ],
        ];

        $result = (new ConstantConfigurationParser())->getConfigurationAsValuedArray($rawConfiguration);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function getConfigurationAsValuedIgnoresConstantsInConditions(): void
    {
        // ensure condition would match if evaluated
        Environment::initialize(
            new ApplicationContext('Development'),
            Environment::isComposerMode(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $rawConfiguration = '
# cat=basic/xxx/011; type=options[de,en,fr,zh]; label= options[de, en, fr, zh] a drop down option field
t7 = de

[applicationContext == "Development"]
# cat=basic/xxx/010; type=boolean; label= enable: a checkbox
t8 = 1
[global]

# cat=basic/xxx/010; type=small; label= small a short input field
t9 = xyz';

        $expected = [
            't7' => [
                'cat' => 'basic',
                'subcat' => 'x/011z',
                'type' => 'options[de,en,fr,zh]',
                'label' => 'options[de, en, fr, zh] a drop down option field',
                'name' => 't7',
                'value' => 'de',
                'default_value' => 'de',
            ],
            't9' => [
                'cat' => 'basic',
                'subcat' => 'x/010z',
                'type' => 'small',
                'label' => 'small a short input field',
                'name' => 't9',
                'value' => 'xyz',
                'default_value' => 'xyz',
            ],
        ];

        $result = (new ConstantConfigurationParser())->getConfigurationAsValuedArray($rawConfiguration);
        self::assertSame($expected, $result);
    }
}
