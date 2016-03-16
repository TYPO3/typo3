<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

/**
 * Create data for a specific table and its child tables
 */
class RecordData
{
    /**
     * Generate data for a given table and insert into database
     *
     * @param string $tableName The tablename to create data for
     * @param array $fieldValues Incoming list of field values, Typically uid and pid are set already
     * @return array
     * @throws Exception
     */
    public function generate(string $tableName, array $fieldValues): array
    {
        $tca = $GLOBALS['TCA'][$tableName];
        /** @var FieldGeneratorResolver $resolver */
        $resolver = GeneralUtility::makeInstance(FieldGeneratorResolver::class);
        foreach ($tca['columns'] as $fieldName => $fieldConfig) {
            // Generate only if there is no value set, yet
            if (isset($fieldValues[$fieldName])) {
                continue;
            }
            $data = [
                'tableName' => $tableName,
                'fieldName' => $fieldName,
                'fieldConfig' => $fieldConfig,
                'fieldValues' => $fieldValues,
            ];
            try {
                $generator = $resolver->resolve($data);
                $fieldValues[$fieldName] = $generator->generate($data);
            } catch (GeneratorNotFoundException $e) {
                // No op if no matching generator was found
            }
        }
        return $fieldValues;
    }

}