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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Avatar renderer class
 */
class Avatar
{
    /**
     * Array of sorted and initiated avatar providers
     *
     * @var AvatarProviderInterface[]
     */
    protected $avatarProviders = [];

    /**
     * Construct
     */
    public function __construct()
    {
        $this->validateSortAndInitiateAvatarProviders();
    }

    /**
     * Render avatar tag
     *
     * @param array $backendUser be_users record
     * @param int $size width and height of the image
     * @param bool $showIcon show the record icon
     * @return string
     */
    public function render(array $backendUser = null, $size = 32, $showIcon = false)
    {
        if (!is_array($backendUser)) {
            $backendUser = $this->getBackendUser()->user;
        }

        // Icon
        $icon = '';
        if ($showIcon) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $icon = '<span class="avatar-icon">' . $iconFactory->getIconForRecord('be_users', $backendUser, Icon::SIZE_SMALL)->render() . '</span>';
        }

        $image = $this->getImgTag($backendUser, $size);

        return '<span class="avatar"><span class="avatar-image">' . $image . '</span>' . $icon . '</span>';
    }

    /**
     * Get avatar img tag
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

        $avatarImage = $this->getImage($backendUser, $size);

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
     * @return Image|NULL
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
     * @return void
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
                throw new \RuntimeException('Missing configuration for avatar provider "' . $identifier . '".', 1439317801);
            }
            if (!is_string($configuration['provider']) || empty($configuration['provider']) || !class_exists($configuration['provider']) || !is_subclass_of($configuration['provider'], AvatarProviderInterface::class)) {
                throw new \RuntimeException('The avatar provider "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . AvatarProviderInterface::class . '".', 1439317802);
            }
        }

        $orderedProviders = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($providers);

        // Initiate providers
        foreach ($orderedProviders as $configuration) {
            $this->avatarProviders[] = GeneralUtility::makeInstance($configuration['provider']);
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
