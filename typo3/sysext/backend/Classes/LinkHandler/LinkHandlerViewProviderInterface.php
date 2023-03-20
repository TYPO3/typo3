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

namespace TYPO3\CMS\Backend\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\View\ViewInterface;

interface LinkHandlerViewProviderInterface
{
    public function createView(BackendViewFactory $backendViewFactory, ServerRequestInterface $request): ViewInterface;
    public function setView(ViewInterface $view): self;
    public function getView(): ViewInterface;
}
