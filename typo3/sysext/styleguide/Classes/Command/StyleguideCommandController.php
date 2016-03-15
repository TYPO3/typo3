<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\Command;

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

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;

/**
 * Styleguide commands
 */
class StyleguideCommandController extends CommandController
{
    /**
     * @var bool
     */
    protected $requestAdminPermissions = true;

    /**
     * Random Kauderwelsch quote
     *
     * @return string
     * @cli
     */
    public function KauderwelschCommand()
    {
        /** @var $service KauderwelschService */
        $service = $this->objectManager->get(KauderwelschService::class);
        return $service->getLoremIpsumHtml();
    }

}
