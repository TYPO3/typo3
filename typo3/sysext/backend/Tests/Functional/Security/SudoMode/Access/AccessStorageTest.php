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

namespace TYPO3\CMS\Backend\Tests\Functional\Security\SudoMode\Access;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessClaim;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Access\RouteAccessSubject;
use TYPO3\CMS\Backend\Security\SudoMode\Access\ServerRequestInstruction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AccessStorageTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    public static function claimCanBeStoredAndRetrievedWithServerParamsDataProvider(): \Generator
    {
        yield 'valid UTF-8 in serverParams' => [
            'submittedServerParams' => ['GEOIP_CITY' => "D\u{00FC}sseldorf"],
            'expectedServerParams' => ['GEOIP_CITY' => "D\u{00FC}sseldorf"],
        ];
        yield 'invalid UTF-8 (Latin-1 encoded) in serverParams' => [
            'submittedServerParams' => ['GEOIP_CITY' => "D\xFCsseldorf"],
            // the latin-1 encoded value `\xFC` is destroyed here to have proper JSON
            'expectedServerParams' => ['GEOIP_CITY' => "D\u{FFFD}sseldorf"],
        ];
    }

    #[Test]
    #[DataProvider('claimCanBeStoredAndRetrievedWithServerParamsDataProvider')]
    public function claimCanBeStoredAndRetrievedWithServerParams(array $submittedServerParams, array $expectedServerParams): void
    {
        $accessStorage = $this->get(AccessStorage::class);
        $expiration = time() + AccessLifetime::medium->inSeconds();

        $request = new ServerRequest('https://example.com/test', 'GET', 'php://input', [], $submittedServerParams);
        $instruction = ServerRequestInstruction::createForServerRequest($request);
        $subject = new RouteAccessSubject('/module/test');
        $claim = new AccessClaim($instruction, $expiration, null, null, $subject);

        $accessStorage->addClaim($claim);
        $retrievedClaim = $accessStorage->findClaimById($claim->id);

        self::assertNotNull($retrievedClaim, 'Claim must not be silently lost');
        self::assertSame($claim->id, $retrievedClaim->id);
        self::assertSame($expiration, $retrievedClaim->expiration);
        self::assertCount(1, $retrievedClaim->subjects);
        self::assertSame('/module/test', $retrievedClaim->subjects[0]->getSubject());
        self::assertSame($expectedServerParams, $retrievedClaim->instruction->getServerParams());
    }
}
