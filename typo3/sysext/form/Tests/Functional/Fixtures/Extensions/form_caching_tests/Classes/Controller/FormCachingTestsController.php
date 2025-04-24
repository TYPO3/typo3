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

namespace TYPO3Tests\FormCachingTests\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class FormCachingTestsController extends ActionController
{
    public function someRenderAction(): ResponseInterface
    {
        $this->view->assign('formIdentifier', $this->request->getPluginName() . '-' . $this->request->getAttribute('currentContentObject')->data['uid']);
        $this->view->assign('pluginName', $this->request->getPluginName());
        return $this->htmlResponse();
    }

    public function somePerformAction(): ResponseInterface
    {
        $this->view->assign('formIdentifier', $this->request->getPluginName() . '-' . $this->request->getAttribute('currentContentObject')->data['uid']);
        $this->view->assign('pluginName', $this->request->getPluginName());
        return $this->htmlResponse();
    }
}
