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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Adminpanel interface to denote that a module provides data to be stored for the current request
 *
 * Adminpanel modules can save data to the adminpanel request cache and access this data in the rendering process.
 * Data necessary for rendering the module content has to be returned via this interface implementation, as this allows
 * for separate data collection and rendering and is a pre-requisite for a standalone debug tool.
 */
interface DataProviderInterface
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData;
}
