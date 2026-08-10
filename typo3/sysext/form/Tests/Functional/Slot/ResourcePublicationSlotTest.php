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

namespace TYPO3\CMS\Form\Tests\Functional\Slot;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Controller\FileDumpController;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Form\Slot\ResourcePublicationSlot;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourcePublicationSlotTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Files/regular-file.txt' => 'fileadmin/user_upload/regular-file.txt',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_file.csv');
        $normalizedParams = NormalizedParams::createFromServerParams(['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://localhost/', 'GET')
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }

    #[Test]
    public function generatedFileDumpUrlIsAcceptedByFileDumpController(): void
    {
        $file = $this->get(ResourceFactory::class)->getFileObject(1);
        self::assertInstanceOf(File::class, $file);

        $subject = $this->get(ResourcePublicationSlot::class);
        $subject->add($file);
        $event = new GeneratePublicUrlForResourceEvent($file, $file->getStorage(), new LocalDriver());
        $subject->getPublicUrl($event);

        parse_str((string)parse_url((string)$event->getPublicUrl(), PHP_URL_QUERY), $queryParameters);
        self::assertSame('dumpFile', $queryParameters['eID'] ?? null);

        $request = new ServerRequest('https://localhost/index.php', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withQueryParams($queryParameters);

        self::assertSame(200, $this->get(FileDumpController::class)->dumpAction($request)->getStatusCode());
    }
}
