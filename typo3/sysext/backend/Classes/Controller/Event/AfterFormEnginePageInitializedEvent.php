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

namespace TYPO3\CMS\Backend\Controller\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\EditDocumentController;

/**
 * Event to listen to after the form engine has been initialized (= all data has been persisted)
 */
final class AfterFormEnginePageInitializedEvent
{
    /**
     * @var EditDocumentController
     */
    private $controller;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(EditDocumentController $controller, ServerRequestInterface $request)
    {
        $this->controller = $controller;
        $this->request = $request;
    }

    public function getController(): EditDocumentController
    {
        return $this->controller;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
