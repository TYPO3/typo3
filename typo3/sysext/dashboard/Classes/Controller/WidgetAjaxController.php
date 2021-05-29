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

namespace TYPO3\CMS\Dashboard\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Dashboard\Dashboard;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * @internal
 */
class WidgetAjaxController extends AbstractController
{
    /**
     * @var Dashboard|null
     */
    protected $currentDashboard;

    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

    /**
     * @var WidgetRegistry
     */
    protected $widgetRegistry;

    public function __construct(DashboardRepository $dashboardRepository, WidgetRegistry $widgetRegistry)
    {
        $this->dashboardRepository = $dashboardRepository;
        $this->widgetRegistry = $widgetRegistry;

        $this->currentDashboard = $this->dashboardRepository->getDashboardByIdentifier($this->loadCurrentDashboard());
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getContent(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        try {
            $widgetObject = $this->widgetRegistry->getAvailableWidget((string)$queryParams['widget']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Widget is not available!']);
        }

        if (!$widgetObject instanceof WidgetInterface) {
            return new JsonResponse(['error' => 'Widget doesn\'t have a valid widget class']);
        }

        $data = [
            'widget' => $queryParams['widget'],
            'content' => $widgetObject->renderWidgetContent(),
            'eventdata' => $widgetObject instanceof EventDataInterface ? $widgetObject->getEventData() : [],
        ];

        return new JsonResponse($data);
    }

    /**
     * Get the order of the widgets from the request and save the order in the database by using the repository
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function savePositions(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $widgets = [];
        foreach ($body['widgets'] as $widget) {
            $widgets[$widget[1]] = ['identifier' => $widget[0]];
        }

        if ($this->currentDashboard !== null) {
            $this->dashboardRepository->updateWidgetConfig($this->currentDashboard, $widgets);
        }
        return new JsonResponse(['status' => 'saved']);
    }
}
