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
 * Generate data for type=group fields
 */
class TypeGroupDbAllowedBeUsersBeGroups extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    /**
     * @var array General match if type=group
     */
    protected $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
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
        $beGroupUids = $recordFinder->findUidsOfDemoBeGroups();
        $beUserUids = $recordFinder->findUidsOfDemoBeUsers();
        $result = [];
        foreach ($beGroupUids as $uid) {
            $result[] = 'be_groups_' . $uid;
        }
        foreach ($beUserUids as $uid) {
            $result[] = 'be_users_' . $uid;
        }
        return implode(',', $result);
    }
}
