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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Redirects\Repository\Demand;

#[AsEventListener]
readonly class QrCodeNewDocHeaderButton
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $buttons = $event->getButtons();
        $request = $event->getRequest();

        // Overwrite the "new" button only if there is already one in
        // the qrcodes module. This way the show/hide logic is re-used
        if (!(($buttons['left'][4][0] ?? false) && ($request->getQueryParams()['module'] ?? '') === 'qrcodes')) {
            return;
        }

        $newQrCodeUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => ['sys_redirect' => ['new']],
                'module' => 'qrcodes',
                'defVals' => [
                    'sys_redirect' => [
                        'redirect_type' => Demand::QRCODE_REDIRECT_TYPE,
                    ],
                ],
                'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('qrcodes'),
            ]
        );

        $languageService = $this->getLanguageService();
        $newRecordButton = $this->componentFactory->createLinkButton()
            ->setHref($newQrCodeUrl)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:new'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));

        // Overwrite the default "new" button
        $buttons['left'][4][0] = $newRecordButton;
        $event->setButtons($buttons);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
