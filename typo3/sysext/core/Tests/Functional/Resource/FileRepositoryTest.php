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
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['frontend'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileRepository.csv');
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', 1);
    }

    #[Test]
    public function findByRelationReturnsReferenceOfMovedRecordOnlyOnceInWorkspace(): void
    {
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $references = $this->get(FileRepository::class)->findByRelation('tt_content', 'image', 30);

        self::assertCount(1, $references);
    }
}
