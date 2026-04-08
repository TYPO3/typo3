<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\FormDataGroup;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\FormDataGroupInterface;

/**
 * A data provider group for backend user settings configuration.
 *
 * This data group is for the user settings form in the profile settings module.
 * It uses a simplified set of providers since user settings data comes
 * from BE_USER->user and BE_USER->uc, not from a regular database record.
 *
 * @internal
 */
#[Autoconfigure(public: true, shared: false)]
readonly class UserSettingsDataGroup implements FormDataGroupInterface
{
    public function __construct(
        private OrderedProviderList $orderedProviderList,
    ) {}

    public function compile(array $result): array
    {
        $this->orderedProviderList->setProviderList(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['backendUserSettings']
        );
        return $this->orderedProviderList->compile($result);
    }
}
