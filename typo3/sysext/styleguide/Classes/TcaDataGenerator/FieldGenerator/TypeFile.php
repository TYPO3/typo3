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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=file fields
 *
 * @internal
 */
final class TypeFile extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'file',
            ],
        ],
    ];

    public function __construct(private readonly RecordFinder $recordFinder) {}

    public function generate(array $data): int
    {
        $demoImages = $this->recordFinder->findDemoFileObjects();
        $recordData = [];

        // By default, insert all available images (3).
        // Unless max/minitems are set. Required extra "min" items are filled with duplicates
        // of the first demoImage.
        $numberOfDemoImages = count($demoImages);
        $itemsToInsert = $data['fieldConfig']['config']['minitems'] ?? $numberOfDemoImages;
        $itemsLimit = $data['fieldConfig']['config']['maxitems'] ?? $numberOfDemoImages;
        $itemsActuallyInserted = 0;
        // Fill up demo Images with a duplicate
        $missingNumberOfImages = $itemsToInsert - $numberOfDemoImages;
        if ($missingNumberOfImages > 0) {
            // Get the first demo image as boilerplate
            $demoImageToDuplicate = reset($demoImages);
            for ($i = 0; $i < $missingNumberOfImages; $i++) {
                $demoImages[] = $demoImageToDuplicate;
            }
        }
        foreach ($demoImages as $demoImage) {
            if ($itemsActuallyInserted >= $itemsLimit) {
                // No more image insertion, the threshold of "max" has been reached.
                continue;
            }
            $itemsActuallyInserted++;
            $newId = StringUtility::getUniqueId('NEW');
            $recordData['sys_file_reference'][$newId] = [
                'table_local' => 'sys_file',
                'uid_local' => $demoImage->getUid(),
                'uid_foreign' => $data['fieldValues']['uid'],
                'tablenames' => $data['tableName'],
                'fieldname' => $data['fieldName'],
                'pid' => $data['fieldValues']['pid'],
            ];
        }
        if (!empty($recordData)) {
            // Populate page tree via recordDataHandler
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->enableLogging = false;
            $dataHandler->start($recordData, []);
            $dataHandler->process_datamap();
        }
        return $itemsActuallyInserted;
    }
}
