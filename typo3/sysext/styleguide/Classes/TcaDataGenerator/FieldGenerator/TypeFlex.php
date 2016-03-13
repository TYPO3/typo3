<?php
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorResolver;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorNotFoundException;

/**
 * Generate data for type=flex fields
 */
class TypeFlex extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=input
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'flex',
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
        // Parse the flex form
        $dataStructureArray = BackendUtility::getFlexFormDS(
            $data['fieldConfig']['config'],
            [],
            $data['tableName'],
            $data['fieldName']
        );

        // Early return if flex couldn't be parsed
        if (!is_array($dataStructureArray)) {
            return '';
        }

        // Loop through this xml mess and call a generator for each found field
        $aFlexFieldData = $data;
        $resultArray = [];
        if (isset($dataStructureArray['sheets']) && is_array($dataStructureArray['sheets'])) {
            /** @var FieldGeneratorResolver $resolver */
            $resolver = GeneralUtility::makeInstance(FieldGeneratorResolver::class);
            foreach ($dataStructureArray['sheets'] as $sheetName => $sheetArray) {
                if (isset($sheetArray['ROOT']['el']) && is_array($sheetArray['ROOT']['el'])) {
                    foreach ($sheetArray['ROOT']['el'] as $sheetElementName => $sheetElementArray) {
                        if (!isset($sheetElementArray['TCEforms']) || !is_array($sheetElementArray['TCEforms'])) {
                            continue;
                        }
                        $aFlexFieldData['fieldName'] = $sheetElementName;
                        $aFlexFieldData['fieldConfig'] = $sheetElementArray['TCEforms'];
                        try {
                            $generator = $resolver->resolve($aFlexFieldData);
                            $flexFieldvalue = $generator->generate($aFlexFieldData);
                            $resultArray['data'][$sheetName]['lDEF'][$sheetElementName]['vDEF'] = $flexFieldvalue;
                        } catch (GeneratorNotFoundException $e) {
                            // No op if no matching generator was found
                        }
                    }
                }
            }
        }
        $resultString = '';
        if (!empty($resultArray)) {
            /** @var FlexFormTools $flexFormTools */
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $resultString = $flexFormTools->flexArray2Xml($resultArray, true);
        }
        return $resultString;
    }
}
