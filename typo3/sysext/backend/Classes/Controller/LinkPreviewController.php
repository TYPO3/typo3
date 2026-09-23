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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolves the href of a link (e.g. "t3://page?uid=3") to human readable
 * information, e.g. for the link balloon and Ctrl+Click in the RTE.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class LinkPreviewController
{
    public function __construct(
        private LinkService $linkService,
        private SiteFinder $siteFinder,
        private IconFactory $iconFactory,
    ) {}

    public function resolveAction(ServerRequestInterface $request): ResponseInterface
    {
        $href = trim((string)($request->getQueryParams()['href'] ?? ''));
        $result = [
            'type' => null,
            'title' => null,
            'path' => null,
            'url' => null,
            'icon' => 'actions-link',
        ];
        if ($href === '') {
            return new JsonResponse($result);
        }
        try {
            $linkDetails = $this->linkService->resolve($href);
        } catch (UnknownLinkHandlerException|UnknownUrnException) {
            return new JsonResponse($result);
        } catch (\Throwable) {
            return new JsonResponse($result);
        }
        $type = (string)($linkDetails['type'] ?? '');
        $result['type'] = $type;
        $result = match ($type) {
            LinkService::TYPE_PAGE => $this->resolvePage($linkDetails, $result),
            LinkService::TYPE_FILE => $this->resolveFile($linkDetails, $result),
            LinkService::TYPE_FOLDER => $this->resolveFolder($linkDetails, $result),
            LinkService::TYPE_URL => $this->withTitleAndUrl($result, $linkDetails['url'] ?? '', $linkDetails['url'] ?? '', 'actions-link'),
            LinkService::TYPE_EMAIL => $this->withTitleAndUrl($result, $linkDetails['email'] ?? '', 'mailto:' . ($linkDetails['email'] ?? ''), 'actions-envelope'),
            LinkService::TYPE_TELEPHONE => $this->withTitleAndUrl($result, $linkDetails['telephone'] ?? '', 'tel:' . ($linkDetails['telephone'] ?? ''), 'actions-phone'),
            default => $result,
        };
        return new JsonResponse($result);
    }

    private function resolvePage(array $linkDetails, array $result): array
    {
        $pageUid = $linkDetails['pageuid'] ?? null;
        if (!MathUtility::canBeInterpretedAsInteger($pageUid)) {
            return $result;
        }
        $pageUid = (int)$pageUid;
        $backendUser = $this->getBackendUser();
        $permissionsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $page = BackendUtility::readPageAccess($pageUid, $permissionsClause);
        if ($page === false || $pageUid === 0) {
            return $result;
        }
        BackendUtility::workspaceOL('pages', $page);
        $result['title'] = (string)$page['title'];
        $result['path'] = BackendUtility::getRecordPath($pageUid, $permissionsClause, 1000);
        $result['icon'] = $this->iconFactory->getIconForRecord('pages', $page, IconSize::SMALL)->getIdentifier();
        $parameters = [];
        parse_str($linkDetails['parameters'] ?? '', $parameters);
        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
            $uri = $site->getRouter()->generateUri(
                $pageUid,
                $parameters,
                (string)($linkDetails['fragment'] ?? ''),
                '',
            );
            $result['url'] = (string)$uri;
        } catch (SiteNotFoundException) {
            // Pages outside of a site can not be linked to in the frontend
        }
        return $result;
    }

    private function resolveFile(array $linkDetails, array $result): array
    {
        $file = $linkDetails['file'] ?? null;
        if (!$file instanceof File || !$file->checkActionPermission('read') || $file->getStorage()->isFallbackStorage()) {
            return $result;
        }
        $result['title'] = $file->getName();
        $result['path'] = $file->getStorage()->getUid() . ':' . $file->getIdentifier();
        $result['url'] = $file->getPublicUrl();
        $result['icon'] = 'apps-filetree-file';
        return $result;
    }

    private function resolveFolder(array $linkDetails, array $result): array
    {
        $folder = $linkDetails['folder'] ?? null;
        if (!$folder instanceof Folder || !$folder->checkActionPermission('read') || $folder->getStorage()->isFallbackStorage()) {
            return $result;
        }
        $result['title'] = $folder->getName();
        $result['path'] = $folder->getCombinedIdentifier();
        $result['icon'] = 'apps-filetree-folder-default';
        return $result;
    }

    private function withTitleAndUrl(array $result, string $title, string $url, string $icon): array
    {
        $result['title'] = $title;
        $result['url'] = $url;
        $result['icon'] = $icon;
        return $result;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
