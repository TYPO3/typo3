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

namespace TYPO3\CMS\Backend\Backend\Avatar;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Main class to render an avatar image of a certain Backend user, resolving any avatar provider
 * that takes care of fetching the image.
 *
 * See render() and getImgTag() as main entry points
 */
class Avatar
{
    /**
     * Sorted and initialized avatar providers
     *
     * @var AvatarProviderInterface[]
     */
    protected array $avatarProviders = [];

    public function __construct(
        protected readonly FrontendInterface $cache,
        protected readonly DependencyOrderingService $dependencyOrderingService,
        protected readonly IconFactory $iconFactory
    ) {
        $this->validateSortAndInitiateAvatarProviders();
    }

    /**
     * Renders an avatar based on a Fluid template which contains some base wrapper css classes.
     * Has a simple caching functionality. Used in Avatar ViewHelper for instance.
     * Renders avatar of a given backend user record, or of current logged-in backend user.
     */
    public function render(array $backendUser = null, int $size = 32, bool $showIcon = false): string
    {
        if (!is_array($backendUser)) {
            /** @var array $backendUser */
            $backendUser = $this->getBackendUser()->user;
        }
        $cacheId = 'avatar_' . sha1($backendUser['uid'] . $size . $showIcon);
        $avatar = $this->cache->get($cacheId);
        if (!$avatar) {
            $icon = $showIcon ? $this->iconFactory->getIconForRecord('be_users', $backendUser, Icon::SIZE_SMALL)->render() : '';
            $avatar =
                '<span class="avatar" style="--avatar-size: ' . $size . 'px;">'
                    . '<span class="avatar-image">' . $this->getImgTag($backendUser, $size) . '</span>'
                    . ($showIcon ? '<span class="avatar-icon">' . $icon . '</span>' : '')
                . '</span>';
            $this->cache->set($cacheId, $avatar);
        }
        return $avatar;
    }

    /**
     * Returns an HTML <img> tag of given backend users avatar.
     */
    protected function getImgTag(array $backendUser, int $size = 32): string
    {
        $avatarImage = $this->getImage($backendUser, $size);
        return '<img src="' . htmlspecialchars($avatarImage->getUrl()) . '" ' .
            'width="' . (int)$avatarImage->getWidth() . '" ' .
            'height="' . (int)$avatarImage->getHeight() . '" />';
    }

    /**
     * Get Image from first provider that returns one.
     */
    protected function getImage(array $backendUser, int $size): Image
    {
        foreach ($this->avatarProviders as $provider) {
            $avatarImage = $provider->getImage($backendUser, $size);
            if (!empty($avatarImage)) {
                return $avatarImage;
            }
        }
        return GeneralUtility::makeInstance(
            Image::class,
            PathUtility::getPublicResourceWebPath('EXT:core/Resources/Public/Icons/T3Icons/svgs/avatar/avatar-default.svg'),
            $size,
            $size
        );
    }

    /**
     * Validates the registered avatar providers
     *
     * @throws \RuntimeException
     */
    protected function validateSortAndInitiateAvatarProviders(): void
    {
        /** @var array<string,array> $providers */
        $providers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders'] ?? [];
        if (empty($providers)) {
            return;
        }
        foreach ($providers as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException(
                    'Missing configuration for avatar provider "' . $identifier . '".',
                    1439317801
                );
            }
            if (!is_string($configuration['provider']) || empty($configuration['provider']) || !class_exists($configuration['provider']) || !is_subclass_of(
                $configuration['provider'],
                AvatarProviderInterface::class
            )) {
                throw new \RuntimeException(
                    'The avatar provider "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . AvatarProviderInterface::class . '".',
                    1439317802
                );
            }
        }
        $orderedProviders = $this->dependencyOrderingService->orderByDependencies($providers);
        foreach ($orderedProviders as $configuration) {
            /** @var AvatarProviderInterface $avatarProvider */
            $avatarProvider = GeneralUtility::makeInstance($configuration['provider']);
            $this->avatarProviders[] = $avatarProvider;
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
