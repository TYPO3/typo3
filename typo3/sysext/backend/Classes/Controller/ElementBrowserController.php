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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * Script class for the Element Browser window.
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
class ElementBrowserController
{
    /**
     * The mode determines the main kind of output of the element browser.
     *
     * There are these options for values:
     *  - "db" will allow you to browse for pages or records in the page tree for FormEngine select fields
     *  - "file" will allow you to browse for files in the folder mounts for FormEngine file selections
     *  - "folder" will allow you to browse for folders in the folder mounts for FormEngine folder selections
     *  - Other options may be registered via extensions
     */
    protected string $mode = '';

    public function __construct(protected readonly ElementBrowserRegistry $elementBrowserRegistry)
    {
    }

    /**
     * Injects the request object for the current request or sub-request
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->mode = $request->getQueryParams()['mode'] ?? $request->getQueryParams()['mode'] ?? '';
        return new HtmlResponse($this->main($request));
    }

    /**
     * Main function, detecting the current mode of the element browser and branching out to internal methods.
     *
     * @return string HTML content
     */
    protected function main(ServerRequestInterface $request)
    {
        $browser = $this->elementBrowserRegistry->getElementBrowser($this->mode);
        if (is_callable([$browser, 'setRequest'])) {
            $browser->setRequest($request);
        }

        $backendUser = $this->getBackendUser();
        $modData = $backendUser->getModuleData('browse_links.php', 'ses');
        [$modData] = $browser->processSessionData($modData);
        $backendUser->pushModuleData('browse_links.php', $modData);

        return $browser->render();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
