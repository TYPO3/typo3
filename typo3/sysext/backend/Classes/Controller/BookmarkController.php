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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Backend\Bookmark\BookmarkService;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Controller for bookmark processing.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class BookmarkController
{
    public function __construct(
        protected readonly BookmarkService $bookmarkService,
    ) {}

    public function listAction(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'success' => true,
            'bookmarks' => $this->bookmarkService->getBookmarks(),
            'groups' => $this->bookmarkService->getGroups(),
        ]);
    }

    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $routeIdentifier = $parsedBody['routeIdentifier'] ?? '';
        $arguments = $parsedBody['arguments'] ?? '';

        if ($routeIdentifier === '') {
            return $this->errorResponse(
                'core.bookmarks:error.missingRoute.message',
                400
            );
        }

        if ($this->bookmarkService->hasBookmark($routeIdentifier, $arguments)) {
            return $this->errorResponse(
                'core.bookmarks:error.createFailed.message'
            );
        }

        $bookmarkName = $parsedBody['displayName'] ?? '';
        $bookmarkId = $this->bookmarkService->createBookmark($routeIdentifier, $arguments, $bookmarkName);

        if ($bookmarkId === false) {
            return $this->errorResponse(
                'core.bookmarks:error.createFailed.message',
                500
            );
        }

        $bookmark = $this->bookmarkService->getBookmark($bookmarkId);

        return new JsonResponse([
            'success' => true,
            'bookmark' => $bookmark,
        ], 201);
    }

    public function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $bookmarkId = (int)($parsedBody['bookmarkId'] ?? 0);
        $bookmarkTitle = trim($parsedBody['bookmarkTitle'] ?? '');
        // Group ID can be int (system group, including negative for global) or string (user-created UUID)
        $bookmarkGroupId = $parsedBody['bookmarkGroup'] ?? 0;
        if (is_numeric($bookmarkGroupId)) {
            $bookmarkGroupId = (int)$bookmarkGroupId;
        }

        if ($bookmarkId === 0) {
            return $this->errorResponse(
                'core.bookmarks:error.missingBookmarkId.message',
                400
            );
        }

        $result = $this->bookmarkService->updateBookmark($bookmarkId, $bookmarkTitle, $bookmarkGroupId);

        if ($result['success']) {
            $bookmark = $this->bookmarkService->getBookmark($bookmarkId);
            if ($bookmark !== null) {
                $result['bookmark'] = $bookmark;
            }
        }

        return new JsonResponse($result);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $bookmarkId = (int)($request->getParsedBody()['bookmarkId'] ?? 0);

        if ($bookmarkId === 0) {
            return $this->errorResponse(
                'core.bookmarks:error.missingBookmarkId.message',
                400
            );
        }

        $result = $this->bookmarkService->deleteBookmark($bookmarkId);

        return new JsonResponse($result);
    }

    public function reorderAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $bookmarkIds = $parsedBody['bookmarkIds'] ?? [];

        if (!is_array($bookmarkIds) || $bookmarkIds === []) {
            return $this->errorResponse(
                'core.bookmarks:error.missingBookmarkIds.message',
                400
            );
        }

        $success = $this->bookmarkService->reorderBookmarks(array_map('intval', $bookmarkIds));

        return new JsonResponse([
            'success' => $success,
            'bookmarks' => $this->bookmarkService->getBookmarks(),
        ]);
    }

    public function deleteMultipleAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $bookmarkIds = $parsedBody['bookmarkIds'] ?? [];

        if (!is_array($bookmarkIds) || $bookmarkIds === []) {
            return $this->errorResponse(
                'core.bookmarks:error.missingBookmarkIds.message',
                400
            );
        }

        $success = $this->bookmarkService->deleteBookmarks(array_map('intval', $bookmarkIds));

        return new JsonResponse(['success' => $success]);
    }

    public function moveAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $bookmarkIds = $parsedBody['bookmarkIds'] ?? [];
        // Group ID can be int (system group, including negative for global) or string (user-created UUID)
        $groupId = $parsedBody['groupId'] ?? 0;
        if (is_numeric($groupId)) {
            $groupId = (int)$groupId;
        }

        if (!is_array($bookmarkIds) || $bookmarkIds === []) {
            return $this->errorResponse(
                'core.bookmarks:error.missingBookmarkIds.message',
                400
            );
        }

        $success = $this->bookmarkService->moveBookmarks(array_map('intval', $bookmarkIds), $groupId);

        return new JsonResponse(['success' => $success]);
    }

    public function createGroupAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $label = trim($parsedBody['label'] ?? '');

        if ($label === '') {
            return $this->errorResponse(
                'core.bookmarks:error.missingLabel.message',
                400
            );
        }

        $group = $this->bookmarkService->createGroup($label);

        if ($group === null) {
            return $this->errorResponse(
                'core.bookmarks:error.groupCreateFailed.message',
                500
            );
        }

        return new JsonResponse([
            'success' => true,
            'group' => $group,
        ], 201);
    }

    public function updateGroupAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $uuid = trim($parsedBody['uuid'] ?? '');
        $label = trim($parsedBody['label'] ?? '');

        if ($uuid === '') {
            return $this->errorResponse(
                'core.bookmarks:error.missingGroupId.message',
                400
            );
        }

        if ($label === '') {
            return $this->errorResponse(
                'core.bookmarks:error.missingLabel.message',
                400
            );
        }

        $success = $this->bookmarkService->updateGroup($uuid, $label);

        if (!$success) {
            return $this->errorResponse(
                'core.bookmarks:error.groupUpdateFailed.message',
                500
            );
        }

        return new JsonResponse([
            'success' => true,
            'groups' => $this->bookmarkService->getGroups(),
        ]);
    }

    public function deleteGroupAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $uuid = trim($parsedBody['uuid'] ?? '');

        if ($uuid === '') {
            return $this->errorResponse(
                'core.bookmarks:error.missingGroupId.message',
                400
            );
        }

        $success = $this->bookmarkService->deleteGroup($uuid);

        if (!$success) {
            return $this->errorResponse(
                'core.bookmarks:error.groupDeleteFailed.message',
                500
            );
        }

        return new JsonResponse([
            'success' => true,
            'groups' => $this->bookmarkService->getGroups(),
        ]);
    }

    public function reorderGroupsAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $uuids = $parsedBody['uuids'] ?? [];

        if (!is_array($uuids) || $uuids === []) {
            return $this->errorResponse(
                'core.bookmarks:error.missingGroupIds.message',
                400
            );
        }

        $success = $this->bookmarkService->reorderGroups($uuids);

        if (!$success) {
            return $this->errorResponse(
                'core.bookmarks:error.groupReorderFailed.message',
                500
            );
        }

        return new JsonResponse([
            'success' => true,
            'groups' => $this->bookmarkService->getGroups(),
        ]);
    }

    private function errorResponse(string $labelKey, int $statusCode = 200): JsonResponse
    {
        $languageService = $this->getLanguageService();
        return new JsonResponse([
            'success' => false,
            'error' => $languageService->sL($labelKey) ?: $labelKey,
        ], $statusCode);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
