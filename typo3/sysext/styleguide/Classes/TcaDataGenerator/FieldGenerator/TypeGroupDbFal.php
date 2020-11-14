<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=group fields
 */
class TypeGroupDbFal extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=group
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file',
                'MM' => 'sys_file_reference',
                'MM_match_fields' => [
                    'fieldname' => 'image_fal_group',
                ],
                'prepend_tname' => true,
            ],
        ],
    ];

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $data
     * @return string
     */
    public function generate(array $data): string
    {
        /** @var RecordFinder $recordFinder */
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoImages = $recordFinder->findDemoFileObjects();
        $recordData = [];
        foreach ($demoImages as $demoImage) {
            $newId = StringUtility::getUniqueId('NEW');
            // @todo: this structure is weird, see the @todo comments in TCA definition, too!
            $recordData['sys_file_reference'][$newId] = [
                'table_local' => 'sys_file',
                'uid_local' => $data['fieldValues']['uid'],
                'uid_foreign' => $demoImage->getUid(),
                'tablenames' => 'sys_file',
                'fieldname' => 'image_fal_group',
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
        return (string)count($demoImages);
    }
}
