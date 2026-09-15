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

namespace TYPO3Tests\PlaywrightHelper\EventListener;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Adds a deliberately disabled doc header button, so tests can verify that
 * such a button keeps its disabled state once the module finished loading.
 *
 * The button is bound to editing the root page record, because that is the one
 * record no other spec opens the edit form for. A dedicated query parameter is
 * not an option: opening a backend route directly wraps it into the backend
 * shell, which only carries over the parameters allowed by the route
 * configuration of `record_edit`.
 *
 * @internal Testing only.
 */
#[AsEventListener]
final readonly class AddDisabledDocHeaderButtonListener
{
    public const BUTTON_TITLE = 'Playwright disabled button';

    public function __construct(
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (($event->getRequest()->getQueryParams()['edit']['pages'][1] ?? '') !== 'edit') {
            return;
        }
        $buttons = $event->getButtons();
        $buttons[ButtonBar::BUTTON_POSITION_RIGHT][1][] = $this->componentFactory->createLinkButton()
            ->setHref('#')
            ->setTitle(self::BUTTON_TITLE)
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-heart', IconSize::SMALL))
            ->setDisabled(true);
        $event->setButtons($buttons);
    }
}
