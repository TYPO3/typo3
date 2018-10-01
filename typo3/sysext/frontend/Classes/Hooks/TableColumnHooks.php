<?php
namespace TYPO3\CMS\Frontend\Hooks;

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
 * Hooks / manipulation data for TCA columns e.g. to sort items within itemsProcFunc
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class TableColumnHooks
{
    /**
     * sort list items (used for plugins, list_type) by name
     * @param array $parameters
     */
    public function sortPluginList(array &$parameters)
    {
        @usort(
            $parameters['items'],
            function ($item1, $item2) {
                return strcasecmp($this->getLanguageService()->sL($item1[0]), $this->getLanguageService()->sL($item2[0]));
            }
        );
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
