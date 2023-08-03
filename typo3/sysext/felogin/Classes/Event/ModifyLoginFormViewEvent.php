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

namespace TYPO3\CMS\FrontendLogin\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidStandaloneViewInterface;

/**
 * Allows to inject custom variables into the login form.
 */
final readonly class ModifyLoginFormViewEvent
{
    public function __construct(
        // @todo: only core ViewInterface in v14.
        private FluidStandaloneViewInterface|ViewInterface $view,
        private ServerRequestInterface $request
    ) {}

    /**
     * @todo: only core ViewInterface in v14.
     */
    public function getView(): FluidStandaloneViewInterface|ViewInterface
    {
        return $this->view;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
