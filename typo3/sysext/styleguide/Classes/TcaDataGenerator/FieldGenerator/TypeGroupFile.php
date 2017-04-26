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

use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=group fields
 */
class TypeGroupFile extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=group
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'uploadfolder' => 'uploads/tx_styleguide/',
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
        // Just in case this dir does not exist yet
        GeneralUtility::mkdir(PATH_site . 'uploads/tx_styleguide');
        $files = [
            'bus_lane.jpg',
            'telephone_box.jpg',
            'underground.jpg',
        ];
        // Copy file to uploads/styleguide
        $finalFileNames = [];
        $counter = 0;
        $max = (int)$data['fieldConfig']['config']['size'] === 1 ? 1 : 999;
        foreach ($files as $fileName) {
            $counter ++;
            if ($counter > $max) {
                break;
            }
            $basicFileUtility = GeneralUtility::makeInstance(BasicFileUtility::class);
            $sourceFile = GeneralUtility::getFileAbsFileName('EXT:styleguide/Resources/Public/Images/Pictures/' . $fileName);
            $targetFile = $basicFileUtility->getUniqueName($sourceFile, PATH_site . 'uploads/tx_styleguide');
            GeneralUtility::upload_copy_move($sourceFile, $targetFile);
            // in case of exception at this point (basename requires parameter, null given) => empty uploads/tx_styleguide
            $finalFileNames[] = basename($targetFile);
        }
        return implode(',', $finalFileNames);
    }
}
