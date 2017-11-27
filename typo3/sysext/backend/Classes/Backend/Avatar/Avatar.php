<?php

namespace TYPO3\CMS\Backend\Backend\Avatar;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main class to render an avatar image of a certain Backend user, resolving any avatar provider
 * that takes care of fetching the image.
 *
 * See render() and getImgTag() as main entry points
 */
class Avatar
{
    /**
     * Array of sorted and initialized avatar providers
     *
     * @var AvatarProviderInterface[]
     */
    protected $avatarProviders = [];

    /**
     * Renders an avatar based on a Fluid template which contains some base wrapper classes and does
     * a simple caching functionality, used in Avatar ViewHelper for instance
     *
     * @param array $backendUser be_users record
     * @param int $size width and height of the image
     * @param bool $showIcon show the record icon
     * @return string
     */
    public function render(array $backendUser = null, int $size = 32, bool $showIcon = false)
    {
        if (!is_array($backendUser)) {
            $backendUser = $this->getBackendUser()->user;
        }

        $cacheId = 'avatar_' . md5(
            $backendUser['uid'] . '/' .
            (string)$size . '/' .
            (string)$showIcon
        );

        $avatar = static::getCache()->get($cacheId);

        if (!$avatar) {
            $this->validateSortAndInitiateAvatarProviders();
            $view = $this->getFluidTemplateObject();

            $view->assignMultiple([
                'image' => $this->getImgTag($backendUser, $size),
                'showIcon' => $showIcon,
                'backendUser' => $backendUser
            ]);
            $avatar = $view->render();
            static::getCache()->set($cacheId, $avatar);
        }

        return $avatar;
    }

    /**
     * Returns an HTML <img> tag for the avatar
     *
     * @param array $backendUser be_users record
     * @param int $size
     * @return string
     */
    public function getImgTag(array $backendUser = null, $size = 32)
    {
        if (!is_array($backendUser)) {
            $backendUser = $this->getBackendUser()->user;
        }

        $avatarImage = false;
        if ($backendUser !== null) {
            $avatarImage = $this->getImage($backendUser, $size);
        }

        if (!$avatarImage) {
            $avatarImage = GeneralUtility::makeInstance(
                Image::class,
                ExtensionManagementUtility::siteRelPath('core') . 'Resources/Public/Icons/T3Icons/avatar/avatar-default.svg',
                $size,
                $size
            );
        }
        $imageTag = '<img src="' . htmlspecialchars($avatarImage->getUrl(true)) . '" ' .
            'width="' . (int)$avatarImage->getWidth() . '" ' .
            'height="' . (int)$avatarImage->getHeight() . '" />';

        return $imageTag;
    }

    /**
     * Get Image from first provider that returns one
     *
     * @param array $backendUser be_users record
     * @param int $size
     * @return Image|null
     */
    public function getImage(array $backendUser, $size)
    {
        foreach ($this->avatarProviders as $provider) {
            $avatarImage = $provider->getImage($backendUser, $size);
            if (!empty($avatarImage)) {
                return $avatarImage;
            }
        }
        return null;
    }

    /**
     * Validates the registered avatar providers
     *
     * @throws \RuntimeException
     */
    protected function validateSortAndInitiateAvatarProviders()
    {
        if (
            empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders'])
        ) {
            return;
        }
        $providers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders'];
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

        $orderedProviders = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($providers);

        // Initializes providers
        foreach ($orderedProviders as $configuration) {
            $this->avatarProviders[] = GeneralUtility::makeInstance($configuration['provider']);
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename = null): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        if ($filename === null) {
            $filename = 'Main.html';
        }

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/Avatar/' . $filename));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    /**
     * @return FrontendInterface
     */
    protected function getCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }
}
