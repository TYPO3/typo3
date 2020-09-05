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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

use Codeception\Module;

/**
 * Helper to expose params of Codeception suites to tests.
 *
 *
 * Example 1 "Retrieve configuration param of other module"
 *
 * suite.yml:
 * ----------
 * modules:
 *   enabled:
 *     - WebDriver:
 *         browser: chrome
 *
 * ConfigCest.php
 * --------------
 * $webDriverUrl = $I->grabModuleConfig('WebDriver', 'browser');
 *
 *
 * Example 2 "Expose arbitrary configuration params to tests"
 *
 * suite.yml:
 * ----------
 * modules:
 *   config:
 *     - TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Config:
 *         myCustomParam: myCustomValue
 *
 * ConfigCest.php
 * --------------
 * $myCustomName = $I->grabConfig('myCustomParam');
 */
class Config extends Module
{
    /**
     * Retrieves configuration of this module.
     *
     * @param string|null $parameter
     * @return array|string|null
     * @throws \Codeception\Exception\ModuleException
     */
    public function grabConfig(string $parameter = null)
    {
        return $this->grab($parameter);
    }

    /**
     * Retrieves configuration of a different module.
     *
     * @param string $moduleName
     * @param string|null $parameter
     * @return array|string|null
     * @throws \Codeception\Exception\ModuleException
     */
    public function grabModuleConfig(string $moduleName, string $parameter = null)
    {
        return $this->grab($parameter, $moduleName);
    }

    /**
     * Retrieves configuration of a module.
     *
     * @param string|null $parameter
     * @param string|null $moduleName
     * @return array|string|null
     * @throws \Codeception\Exception\ModuleException
     */
    protected function grab(string $parameter = null, string $moduleName = null)
    {
        $module = is_string($moduleName) ? $this->getModule($moduleName) : $this;
        return $module->_getConfig($parameter);
    }
}
