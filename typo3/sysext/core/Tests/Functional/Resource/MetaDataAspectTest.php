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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MetaDataAspectTest extends FunctionalTestCase
{
    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ProcessedFileTest.jpg' => 'fileadmin/ProcessedFileTest.jpg',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/MetaDataAspect/TranslatedMetaData.csv');
        // The language overlay of file metadata is only applied in frontend context
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    #[Test]
    public function metaDataIsResolvedForTheLanguageOfTheCurrentContext(): void
    {
        $this->setLanguageAspect(1);
        self::assertSame('DE file title', $this->get(ResourceFactory::class)->getFileObject(1)->getProperty('title'));
    }

    #[Test]
    public function metaDataIsResolvedAgainWhenTheLanguageChangesWithinTheSameRequest(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);

        $this->setLanguageAspect(0);
        self::assertSame('EN file title', $resourceFactory->getFileObject(1)->getProperty('title'));

        $this->setLanguageAspect(1);
        self::assertSame('DE file title', $resourceFactory->getFileObject(1)->getProperty('title'));

        $this->setLanguageAspect(0);
        self::assertSame('EN file title', $resourceFactory->getFileObject(1)->getProperty('title'));
    }

    #[Test]
    public function fileReferencePropertiesFollowTheLanguageOfTheCurrentContext(): void
    {
        $resourceFactory = $this->get(ResourceFactory::class);

        $this->setLanguageAspect(0);
        self::assertSame('EN file title', $resourceFactory->getFileReferenceObject(1)->getProperty('title'));

        $this->setLanguageAspect(1);
        self::assertSame('DE file title', $resourceFactory->getFileReferenceObject(1)->getProperty('title'));
    }

    private function setLanguageAspect(int $languageId): void
    {
        $this->get(Context::class)->setAspect(
            'language',
            new LanguageAspect($languageId, $languageId, LanguageAspect::OVERLAYS_MIXED, [0])
        );
    }
}
