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

namespace TYPO3\CMS\Backend\Template\Components;

/**
 * PSR-14 event that allows listeners to modify the buttons in the backend module
 * document header button bar. This event is dispatched after all buttons have been
 * added to the button bar, but before they are rendered.
 *
 * Use cases:
 * - Add custom buttons to existing modules
 * - Remove or hide buttons based on conditions
 * - Modify button properties (labels, icons, etc.)
 * - Reorder buttons
 *
 * Example event listener:
 *
 * ```
 * use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
 * use TYPO3\CMS\Backend\Template\Components\ButtonBar;
 * use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
 * use TYPO3\CMS\Core\Attribute\AsEventListener;
 *
 * final class MyButtonBarListener
 * {
 *     public function __construct(
 *         protected readonly ComponentFactory $componentFactory,
 *     ) {}
 *
 *     #[AsEventListener]
 *     public function __invoke(ModifyButtonBarEvent $event): void
 *     {
 *         $buttons = $event->getButtons();
 *         $buttonBar = $event->getButtonBar();
 *
 *         $myButton = $this->componentFactory->createLinkButton()
 *             ->setHref('/my-action')
 *             ->setTitle('My Action')
 *             ->setIcon($iconFactory->getIcon('actions-heart'));
 *
 *         $buttons[ButtonBar::BUTTON_POSITION_RIGHT][1][] = clone $myButton;
 *
 *         $event->setButtons($buttons);
 *     }
 * }
 * ```
 *
 * @phpstan-import-type Buttons from ButtonBar
 */
final class ModifyButtonBarEvent
{
    /**
     * @param Buttons $buttons
     */
    public function __construct(private array $buttons, private readonly ButtonBar $buttonBar) {}

    /**
     * @return Buttons
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * @param Buttons $buttons
     */
    public function setButtons(array $buttons): void
    {
        $this->buttons = $buttons;
    }

    public function getButtonBar(): ButtonBar
    {
        return $this->buttonBar;
    }
}
