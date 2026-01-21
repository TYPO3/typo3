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

namespace TYPO3\CMS\Workspaces\EventListener;

use TYPO3\CMS\Backend\Template\Components\Event\ModifyPreviewUrlForQrCodeEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;

/**
 * Event listener that modifies the preview URL for QR codes when in workspace context.
 *
 * When a backend user is working in a workspace, this listener generates a workspace preview URL
 * with ADMCMD_prev parameter that allows previewing the page without backend authentication.
 * This is particularly useful for scanning QR codes with mobile devices.
 *
 * @internal
 */
final readonly class ModifyPreviewUrlForQrCodeListener
{
    public function __construct(
        private PreviewUriBuilder $previewUriBuilder,
    ) {}

    #[AsEventListener('typo3-workspaces/modify-preview-url-for-qrcode')]
    public function __invoke(ModifyPreviewUrlForQrCodeEvent $event): void
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->workspace <= 0) {
            return;
        }

        try {
            $previewUrl = $this->previewUriBuilder->buildUriForPage($event->getPageId(), $event->getLanguageId());
            if ($previewUrl !== '') {
                $event->setPreviewUrl($previewUrl);
            }
        } catch (UnableToLinkToPageException) {
            // Fall back to the default preview URL
        }
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
