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

namespace TYPO3\CMS\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Interface for Backend Login providers
 */
interface LoginProviderInterface
{
    /**
     * Interface to render the backend login view.
     * See UsernamePasswordLoginProvider on how this can be used.
     *
     * @return string Template file to render
     */
    public function modifyView(ServerRequestInterface $request, ViewInterface $view): string;
}
