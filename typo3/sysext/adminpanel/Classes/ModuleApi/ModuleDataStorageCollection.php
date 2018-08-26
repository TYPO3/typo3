<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\ModuleApi;

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

/**
 * ModuleDataStorageCollection is an object storage for adminpanel modules and their data
 */
class ModuleDataStorageCollection extends \SplObjectStorage
{
    public function addModuleData(DataProviderInterface $module, ModuleData $moduleData): void
    {
        $this->attach($module, $moduleData);
    }

    public function getHash($object)
    {
        if ($object instanceof ModuleInterface) {
            return $object->getIdentifier();
        }
        throw new \InvalidArgumentException(
            'Only modules implementing ' . ModuleInterface::class . ' are allowed to be stored',
            1535301628
        );
    }
}
