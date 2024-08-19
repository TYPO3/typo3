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

namespace TYPO3\CMS\Core\Tests\Functional\LinkHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\FileLinkHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileLinkHandlerTest extends FunctionalTestCase
{
    public static function resolveParametersForBrokenFilesDataProvider(): array
    {
        return [
            'file with empty identifier' => [
                'input' => [
                    'identifier' => '',
                ],
            ],
            'file with empty uid' => [
                'input' => [
                    'uid' => '',
                ],
            ],
            'file with broken uid' => [
                'input' => [
                    'uid' => -42,
                ],
            ],
        ];
    }

    #[DataProvider('resolveParametersForBrokenFilesDataProvider')]
    #[Test]
    public function resolveReturnsExpectedResult(array $input): void
    {
        $expected = [
            'file' => null,
        ];
        self::assertSame($expected, (new FileLinkHandler())->resolveHandlerData($input));
    }

    #[Test]
    public function resolveThrowsExceptionWithInvalidIdentifier(): void
    {
        // @todo: This test shouldn't be here, but should be relocated, it tests for an exception
        //        thrown by LocalDriver, and this exception should be turned into a specific
        //        one. See the comment in LocalDriver->getFileInfoByIdentifier()
        $input = [
            'identifier' => 'this-identifier-cant-be-resolved',
        ];
        $subject = new FileLinkHandler();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314516809);
        $subject->resolveHandlerData($input);
    }
}
