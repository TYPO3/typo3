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

namespace TYPO3\CMS\Filelist\Tests\Unit\Event;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModifyEditFileFormDataEventTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $formData = [
            'databaseRow' => [
                'uid' => 123,
            ],
            'tableName' => 'editfile',
            'processedTca' => [
                'columns' => [
                    'data' => [
                        'config' => [
                            'type' => 'someType',
                        ],
                    ],
                ],
            ],
        ];
        $resourceStorageProphecy = $this->prophesize(ResourceStorage::class);
        $file = new File([], $resourceStorageProphecy->reveal());
        $request = new ServerRequest(new Uri('https://example.com'));

        $event = new ModifyEditFileFormDataEvent($formData, $file, $request);

        self::assertEquals($formData, $event->getFormData());
        self::assertEquals($file, $event->getFile());
        self::assertEquals($request, $event->getRequest());

        $modifyFormData = $event->getFormData();
        $modifyFormData['processedTca']['columns']['data']['config']['type'] = 'newType';
        $event->setFormData($modifyFormData);

        self::assertEquals($modifyFormData, $event->getFormData());
    }
}
