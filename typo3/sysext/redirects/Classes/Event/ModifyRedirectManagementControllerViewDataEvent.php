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
    ) {}

    public function getDemand(): Demand
    {
        return $this->demand;
    }

    public function setDemand(Demand $demand): void
    {
        $this->demand = $demand;
    }

    public function getRedirects(): array
    {
        return $this->redirects;
    }

    public function setRedirects(array $redirects): void
    {
        $this->redirects = $redirects;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    public function getStatusCodes(): array
    {
        return $this->statusCodes;
    }

    public function setStatusCodes(array $statusCodes): void
    {
        $this->statusCodes = $statusCodes;
    }

    public function getCreationTypes(): array
    {
        return $this->creationTypes;
    }

    public function setCreationTypes(array $creationTypes): void
    {
        $this->creationTypes = $creationTypes;
    }

    public function getShowHitCounter(): bool
    {
        return $this->showHitCounter;
    }

    public function setShowHitCounter(bool $showHitCounter): void
    {
        $this->showHitCounter = $showHitCounter;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }

    public function setView(ViewInterface $view): void
    {
        $this->view = $view;
    }
}
