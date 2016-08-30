<?php
namespace TYPO3\CMS\Lang\View\Language;

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
 * JSON view for "getTranslations" action in "Language" controller
 */
class GetTranslationsJson extends \TYPO3\CMS\Lang\View\AbstractJsonView
{
    /**
     * Returns the response data
     *
     * @return array The response data
     */
    protected function getReponseData()
    {
        $data = [];
        $languages = $this->variables['languages'];
        foreach ($this->variables['extensions'] as $extension) {
            $extensionArray = $extension->toArray();
            $row = [
                $extensionArray,
                $extensionArray,
            ];
            foreach ($languages as $language) {
                $row[] = $language->toArray();
            }
            $data[] = $row;
        }
        return [
            'data' => $data,
        ];
    }
}
