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

namespace TYPO3\CMS\Frontend\Tests\Functional\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Frontend\Cache\NonceValueSubstitution;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NonceValueSubstitutionTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    public static function nonceIsSubstitutedIfNecessaryDataProvider(): \Generator
    {
        yield 'null on empty content' => [
            'nonce' => new ConsumableNonce(),
            'context' => ['content' => '', 'nonce' => 'PREVIOUS_NONCE_VALUE'],
            'expectedContent' => null,
            'expectedCount' => 0,
        ];
        yield 'null on empty nonce' => [
            'nonce' => new ConsumableNonce(),
            'context' => ['content' => 'Hello PREVIOUS_NONCE_VALUE World', 'nonce' => ''],
            'expectedContent' => null,
            'expectedCount' => 0,
        ];
        yield 'null in case nonce is not contained in content' => [
            'nonce' => new ConsumableNonce(),
            'context' => ['content' => 'Hello MISSING World', 'nonce' => 'PREVIOUS_NONCE_VALUE'],
            'expectedContent' => null,
            'expectedCount' => 0,
        ];
        // create a new nonce value
        $nonce = new ConsumableNonce();
        yield 'null in case nonce did not change' => [
            'nonce' => $nonce,
            'context' => ['content' => 'Hello ' . $nonce->value . ' World', 'nonce' => $nonce->value],
            'expectedContent' => null,
            'expectedCount' => 0,
        ];
        // create a new nonce value
        $nonce = new ConsumableNonce();
        yield 'nonce is substituted & count increased' => [
            'nonce' => $nonce,
            'context' => ['content' => 'Hello PREVIOUS_NONCE_VALUE World', 'nonce' => 'PREVIOUS_NONCE_VALUE'],
            'expectedContent' => 'Hello ' . $nonce->value . ' World',
            'expectedCount' => 1,
        ];
    }

    #[Test]
    #[DataProvider('nonceIsSubstitutedIfNecessaryDataProvider')]
    public function nonceIsSubstitutedIfNecessary(
        ConsumableNonce $nonce,
        array $context,
        ?string $expectedContent,
        ?int $expectedCount,
    ): void {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.org/', 'GET'))
            ->withAttribute('nonce', $nonce);
        $subject = new NonceValueSubstitution();
        $content = $subject->substituteNonce($context);
        self::assertSame($expectedContent, $content);
        self::assertCount($expectedCount, $nonce);
    }
}
