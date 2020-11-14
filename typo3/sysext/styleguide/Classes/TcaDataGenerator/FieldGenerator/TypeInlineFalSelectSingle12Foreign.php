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
 * Generate data for fal_1 field of
 * tx_styleguide_elements_select_single_12_foreign
 */
class TypeInlineFalSelectSingle12Foreign extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * Well ... this one is called twice and we want one image for the first image
     * and a different one for the second image ... this static proprety tracks that
     * since there is no other indication if that is the first or second call ..
     *
     * @var bool
     */
    protected static $first = true;

    /**
     * @var array Match if type=inline in elements_select_single_12_foreign
     */
    protected $matchArray = [
        'fieldName' => 'fal_1',
        'fieldConfig' => [
            'label' => 'fal_1 selicon_field',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_file_reference',
                'foreign_field' => 'uid_foreign',
                'foreign_label' => 'uid_local',
                'foreign_table_field' => 'tablenames',
                'maxitems' => 1,
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
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoImages = $recordFinder->findDemoFileObjects();
        $recordData = [];
        if (self::$first) {
            $demoImage = $demoImages['bus_lane.jpg'];
            self::$first = false;
        } else {
            $demoImage = $demoImages['telephone_box.jpg'];
        }
        $newId = StringUtility::getUniqueId('NEW');
        $recordData['sys_file_reference'][$newId] = [
            'table_local' => 'sys_file',
            'uid_local' => $demoImage->getUid(),
            'uid_foreign' => $data['fieldValues']['uid'],
            'tablenames' => $data['tableName'],
            'fieldname' => $data['fieldName'],
            'pid' => $data['fieldValues']['pid'],
        ];
        if (!empty($recordData)) {
            // Populate page tree via recordDataHandler
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->enableLogging = false;
            $dataHandler->start($recordData, []);
            $dataHandler->process_datamap();
        }
        return (string)1;
    }
}
