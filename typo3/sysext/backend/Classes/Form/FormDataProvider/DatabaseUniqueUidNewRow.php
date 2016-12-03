<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * On "new" command, initialize uid with an unqique uid
 */
class DatabaseUniqueUidNewRow implements FormDataProviderInterface
{
    /**
     * Initialize new row with unique uid
     *
     * @param array $result
     * @return array
     * @throws \InvalidArgumentException
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'new') {
            return $result;
        }
        // Throw exception if uid is already set and does not start with NEW.
        // In some situations a new record needs to be created again so the initialization of default
        // values is triggered, but the "ID" of the new record is already known: This is the case if a
        // new section container element is added by FormFlexAjaxController to a not yet persisted record.
        // In this case, command "new" is given to the data compiler, but the "NEW1234" id has been calculated
        // by the former compiler when opening the record already. The ajax controller then hands in the
        // "new" command together with the id calculated by the first call.
        if (isset($result['databaseRow']['uid']) && strpos($result['databaseRow']['uid'], 'NEW') !== 0) {
            throw new \InvalidArgumentException(
                'uid is already set to ' . $result['databaseRow']['uid'] . ' and does not start with NEW for a "new" command',
                1437991120
            );
        }
        if (!isset($result['databaseRow']['uid'])) {
            $result['databaseRow']['uid'] = StringUtility::getUniqueId('NEW');
        }

        return $result;
    }
}
