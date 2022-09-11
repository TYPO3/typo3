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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * @internal
 */
class WidgetAjaxController
{
    public function __construct(
        protected readonly DashboardRepository $dashboardRepository,
        protected readonly WidgetRegistry $widgetRegistry,
    ) {
    }

    public function getContent(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $widget = (string)($queryParams['widget'] ?? '');
        try {
            $widgetObject = $this->widgetRegistry->getAvailableWidget($request, $widget);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Widget is not available!']);
        }
        if (!$widgetObject instanceof WidgetInterface) {
            return new JsonResponse(['error' => 'Widget doesn\'t have a valid widget class']);
        }
        $data = [
            'widget' => $widget,
            'content' => $widgetObject->renderWidgetContent(),
            'eventdata' => $widgetObject instanceof EventDataInterface ? $widgetObject->getEventData() : [],
        ];
        return new JsonResponse($data);
    }

    /**
     * Get the order of the widgets from the request and save the order in the database by using the repository
     */
    public function savePositions(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $body = $request->getParsedBody();
        $widgets = [];
        foreach ($body['widgets'] ?? [] as $widget) {
            if (!is_string($widget[0] ?? null) || !is_string($widget[1] ?? null)) {
                continue;
            }
            $widgets[$widget[1]] = ['identifier' => $widget[0]];
        }
        $currentDashboard = $this->dashboardRepository->getDashboardByIdentifier($backendUser->getModuleData('dashboard/current_dashboard/') ?? '');
        if ($currentDashboard !== null) {
            $this->dashboardRepository->updateWidgetConfig($currentDashboard, $widgets);
        }
        return new JsonResponse(['status' => 'saved']);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
