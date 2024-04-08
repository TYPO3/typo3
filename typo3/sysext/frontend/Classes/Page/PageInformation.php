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

namespace TYPO3\CMS\Frontend\Page;

use TYPO3\CMS\Core\Page\PageLayout;

/**
 * This DTO carries various Frontend rendering related page information. It is
 * set up by a Frontend middleware and attached to as 'frontend.page.information'
 * Request attribute.
 *
 * @internal Still experimental
 */
final class PageInformation
{
    private int $id;
    private array $pageRecord;
    private string $mountPoint = '';
    private int $contentFromPid;

    /**
     * Gets set when we are processing a page of type shortcut in the early stages
     * of the request, used later in a middleware to resolve the shortcut and redirect again.
     */
    private ?array $originalShortcutPageRecord = null;

    /**
     * Gets set when we are processing a page of type mountpoint with enabled overlay in getPageAndRootline()
     * Used later in a middleware to determine the final target URL where the user should be redirected to.
     */
    private ?array $originalMountPointPageRecord = null;

    /**
     * Rootline of page records all the way to the root.
     *
     * Both language and version overlays are applied to these page records:
     * All "data" fields are set to language / version overlay values, *except* uid and
     * pid, which are the default-language and live-version ids.
     *
     * First array row with the highest key is the deepest page (the requested page),
     * then parent pages with descending keys until (but not including) the
     * project root pseudo page 0.
     *
     * When page uid 5 is called in this example:
     * [0] Project name
     * |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *    |- [3] Site root with a sys_template having "root" flag set
     *       |- [5] Here you are
     *
     * This $absoluteRootLine is:
     * [3] => [uid = 5, pid = 3, title = Here you are, ...]
     * [2] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     * [1] => [uid = 2, pid = 0, title = An organizational page, probably with is_siteroot=1 and a site config, ...]
     *
     * Read-only! Extensions may read but never write this property!
     *
     * @var array<int, array<string, mixed>>
     */
    private array $rootLine;

    /**
     * This is the "local" rootline of a deep page that stops at the first parent
     * sys_template record that has "root" flag set, in natural parent-child order.
     *
     * Both language and version overlays are applied to these page records:
     * All "data" fields are set to language / version overlay values, *except* uid and
     * pid, which are the default-language and live-version ids.
     *
     * When page uid 5 is called in this example:
     * [0] Project name
     * |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *    |- [3] Site root with a sys_template having "root" flag set
     *       |- [5] Here you are
     *
     * This rootLine is:
     * [0] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     * [1] => [uid = 5, pid = 3, title = Here you are, ...]
     *
     * @var array<int, array<string, mixed>>
     */
    private array $localRootLine;

    /**
     * List of all sys_template rows attached to rootLine pages.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $sysTemplateRows;

    /**
     * The resolved PageLayout of the page (selected backend layout)
     *
     * @var PageLayout|null
     */
    private ?PageLayout $pageLayout = null;

    /**
     * @internal Only to be set by core
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @internal Only to be set by core
     */
    public function setPageRecord(array $pageRecord): void
    {
        $this->pageRecord = $pageRecord;
    }

    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    /**
     * @internal Only to be set by core
     */
    public function setMountPoint(string $mountPoint): void
    {
        $this->mountPoint = $mountPoint;
    }

    /**
     * @internal Only to be read by core
     */
    public function getMountPoint(): string
    {
        return $this->mountPoint;
    }

    /**
     * @internal Only to be set by core
     */
    public function setRootLine(array $rootLine): void
    {
        $this->rootLine = $rootLine;
    }

    public function getRootLine(): array
    {
        return $this->rootLine;
    }

    /**
     * @internal Only to be set by core
     */
    public function setLocalRootLine(array $localRootLine): void
    {
        $this->localRootLine = $localRootLine;
    }

    public function getLocalRootLine(): array
    {
        return $this->localRootLine;
    }

    /**
     * @internal Only to be set by core
     */
    public function setSysTemplateRows(array $sysTemplateRows): void
    {
        $this->sysTemplateRows = $sysTemplateRows;
    }

    /**
     * @internal Only to be read by core
     */
    public function getSysTemplateRows(): array
    {
        return $this->sysTemplateRows;
    }

    /**
     * @internal Only to be set by core
     */
    public function setOriginalShortcutPageRecord(array $originalShortcutPageRecord): void
    {
        $this->originalShortcutPageRecord = $originalShortcutPageRecord;
    }

    /**
     * @internal Only to be read by core
     */
    public function getOriginalShortcutPageRecord(): ?array
    {
        return $this->originalShortcutPageRecord;
    }

    /**
     * @internal Only to be set by core
     */
    public function setOriginalMountPointPageRecord(array $originalMountPointPageRecord): void
    {
        $this->originalMountPointPageRecord = $originalMountPointPageRecord;
    }

    /**
     * @internal Only to be read by core
     */
    public function getOriginalMountPointPageRecord(): ?array
    {
        return $this->originalMountPointPageRecord;
    }

    /**
     * @internal Only to be set by core
     */
    public function setContentFromPid(int $contentFromPid): void
    {
        $this->contentFromPid = $contentFromPid;
    }

    /**
     * @internal Only to be read by core
     */
    public function getContentFromPid(): int
    {
        return $this->contentFromPid;
    }

    /**
     * @internal Only to be set by core
     */
    public function setPageLayout(PageLayout $pageLayout): void
    {
        $this->pageLayout = $pageLayout;
    }

    public function getPageLayout(): ?PageLayout
    {
        return $this->pageLayout;
    }
}
