<?php

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * '/empty' routing target returns dummy content.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class DummyController
{
    /**
     * Return simple dummy content
     *
     * @return ResponseInterface the response with the content
     */
    public function mainAction(): ResponseInterface
    {
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $moduleTemplate->setTitle('Blank');
        $moduleTemplate->getDocHeaderComponent()->disable();
        return new HtmlResponse($moduleTemplate->renderContent());
    }
}
