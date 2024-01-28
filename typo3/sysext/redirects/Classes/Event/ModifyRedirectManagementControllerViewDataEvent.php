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

namespace TYPO3\CMS\Redirects\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Redirects\Repository\Demand;

/**
 * This event is fired in the \TYPO3\CMS\Redirects\Controller\ManagementController
 * handleRequest() method.
 *
 * It can be used to further enrich view data for the management view.
 */
final class ModifyRedirectManagementControllerViewDataEvent
{
    public function __construct(
        private Demand $demand,
        private array $redirects,
        private array $hosts,
        private array $statusCodes,
        private array $creationTypes,
        private bool $showHitCounter,
        private ViewInterface $view,
        private readonly ServerRequestInterface $request,
        private array $integrityStatusCodes,
    ) {}

    /**
     * Return the demand object used to retrieve the redirects.
     */
    public function getDemand(): Demand
    {
        return $this->demand;
    }

    /**
     * Can be used to set the demand object.
     */
    public function setDemand(Demand $demand): void
    {
        $this->demand = $demand;
    }

    /**
     * Return the retrieved redirects.
     */
    public function getRedirects(): array
    {
        return $this->redirects;
    }

    /**
     * Can be used to set the redirects, for example, after enriching redirect fields.
     */
    public function setRedirects(array $redirects): void
    {
        $this->redirects = $redirects;
    }

    /**
     * Return the current PSR-7 request.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Returns the hosts to be used for the host filter select box.
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * Can be used to update which hosts are available in the filter select box.
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    /**
     * Returns the status codes for the filter select box.
     */
    public function getStatusCodes(): array
    {
        return $this->statusCodes;
    }

    /**
     * Can be used to update which status codes are available in the filter select box.
     */
    public function setStatusCodes(array $statusCodes): void
    {
        $this->statusCodes = $statusCodes;
    }

    /**
     * Returns creation types for the filter select box.
     */
    public function getCreationTypes(): array
    {
        return $this->creationTypes;
    }

    /**
     * Can be used to update which creation types are available in the filter select box.
     */
    public function setCreationTypes(array $creationTypes): void
    {
        $this->creationTypes = $creationTypes;
    }

    /**
     * Returns, if hit counter should be displayed.
     */
    public function getShowHitCounter(): bool
    {
        return $this->showHitCounter;
    }

    /**
     * Can be used to manage, if the hit counter should be displayed.
     */
    public function setShowHitCounter(bool $showHitCounter): void
    {
        $this->showHitCounter = $showHitCounter;
    }

    /**
     * Returns the current view object, without controller data assigned yet.
     */
    public function getView(): ViewInterface
    {
        return $this->view;
    }

    /**
     * Can be used to assign additional data to the view.
     */
    public function setView(ViewInterface $view): void
    {
        $this->view = $view;
    }

    /**
     * Returns all integrity status codes.
     */
    public function getIntegrityStatusCodes(): array
    {
        return $this->integrityStatusCodes;
    }

    /**
     * Allows to set integrity status codes. It can be used to filter for integrity status codes.
     */
    public function setIntegrityStatusCodes(array $integrityStatusCodes): void
    {
        $this->integrityStatusCodes = $integrityStatusCodes;
    }
}
