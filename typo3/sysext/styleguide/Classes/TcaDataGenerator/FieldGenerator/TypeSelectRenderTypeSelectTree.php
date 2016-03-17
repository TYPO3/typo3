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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=select fields
 */
class TypeSelectRenderTypeSelectTree extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=select
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
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
        $uidOfStyleguideRootPage = $recordFinder->findPidOfMainTableRecord('tx_styleguide');
        $uidOfElementsBasicPage = $recordFinder->findPidOfMainTableRecord('tx_styleguide_elements_basic');
        $uidOfElementsGroupPage = $recordFinder->findPidOfMainTableRecord('tx_styleguide_elements_group');
        $result = [];
        // Don't add first page if not selectable
        if (!isset($data['fieldConfig']['config']['treeConfig']['appearance']['nonSelectableLevels'])) {
            $result[] = $uidOfStyleguideRootPage;
        }
        // Don't add sub pages if maxlevels is set
        if (!isset($data['fieldConfig']['config']['treeConfig']['appearance']['maxLevels'])
            || (isset($data['fieldConfig']['config']['treeConfig']['appearance']['maxLevels'])
                && $data['fieldConfig']['config']['treeConfig']['appearance']['maxLevels'] > 1)
        ) {
            $result[] = $uidOfElementsBasicPage;
            $result[] = $uidOfElementsGroupPage;
        }
        return implode(',', $result);
    }
}
