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

namespace TYPO3\CMS\Form\Tests\Functional\Mvc\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypoScriptServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    #[Test]
    public function resolveTypoScriptConfigurationReturnsResolvedConfiguration(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = (new ServerRequest())->withAttribute('frontend.page.information', $pageInformation);
        $input = [
            'key' => [
                'john' => [
                    '_typoScriptNodeValue' => 'TEXT',
                    'value' => 'rambo',
                ],
            ],
        ];
        $expected = [
            'key' => [
                'john' => 'rambo',
            ],
        ];
        self::assertSame($expected, $this->get(TypoScriptService::class)->resolvePossibleTypoScriptConfiguration($input, $request));
    }
}
