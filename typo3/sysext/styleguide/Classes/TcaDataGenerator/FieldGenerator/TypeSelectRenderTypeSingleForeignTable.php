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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=select fields.
 * Special field for select_single_12
 */
class TypeSelectRenderTypeSingleForeignTable extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=select
     */
    protected $matchArray = [
        'fieldName' => 'select_single_12',
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
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
        // Create 2 child rows in tx_styleguide_elements_select_single_12_foreign
        // and select the first one
        $database = $this->getDatabase();

        // Just in case this dir does not exist yet
        GeneralUtility::mkdir(PATH_site . 'uploads/tx_styleguide');
        $files = [
            'bus_lane.jpg',
            'telephone_box.jpg',
        ];

        $lastUid = '';
        foreach ($files as $fileName) {
            /** @var BasicFileUtility $basicFileUtility */
            $basicFileUtility = GeneralUtility::makeInstance(BasicFileUtility::class);
            $sourceFile = GeneralUtility::getFileAbsFileName('EXT:styleguide/Resources/Public/Images/Pictures/' . $fileName);
            $targetFile = $basicFileUtility->getUniqueName($sourceFile, PATH_site . 'uploads/tx_styleguide');
            GeneralUtility::upload_copy_move($sourceFile, $targetFile);
            $finalFileName = basename($targetFile);

            // Insert an empty row again to have the uid already. This is useful for
            // possible further inline that may be attached to this child.
            $childFieldValues = [
                'pid' => $data['fieldValues']['pid'],
                'group_1' => $finalFileName,
            ];
            $database->exec_INSERTquery('tx_styleguide_elements_select_single_12_foreign', $childFieldValues);
            $lastUid = $database->sql_insert_id();
        }

        return (string)$lastUid;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
