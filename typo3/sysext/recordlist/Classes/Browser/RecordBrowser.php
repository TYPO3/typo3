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

namespace TYPO3\CMS\Recordlist\Browser;

/**
 * Extends the DatabaseBrowser for the specific needs of the LinkBrowser.
 *
 * Mostly this is about being able to set to some parameters that cannot
 * be set from outside the DatabaseBrowser.
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class RecordBrowser extends DatabaseBrowser
{
    protected array $urlParameters = [];

    /**
     * Main initialization
     */
    protected function initialize()
    {
        $this->determineScriptUrl();
        $this->initVariables();
    }

    /**
     * Avoid any initialization
     */
    protected function initVariables()
    {
    }

    /**
     * @param int $selectedPage Id of page
     * @param string $tables Comma separated list of tables
     * @param array $urlParameters url parameters
     *
     * @return string
     */
    public function displayRecordsForPage(int $selectedPage, string $tables, array $urlParameters): string
    {
        $this->urlParameters = $urlParameters;
        $this->urlParameters['mode'] = 'db';
        $this->expandPage = $selectedPage;

        return $this->renderTableRecords($tables);
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values): array
    {
        return array_merge($this->urlParameters, $values);
    }
}
