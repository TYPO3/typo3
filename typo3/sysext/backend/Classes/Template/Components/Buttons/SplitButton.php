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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This button type renders a bootstrap split button.
 * It takes multiple button objects as parameters.
 *
 * The button objects must contain at least one primary
 * button that is displayed as the main icon, and all other
 * items will be revealed within a dropdown.
 *
 * If a button is of Type "LinkButton" it will not utilize a
 * HTML `<button>` tag, but instead use `<a>`.
 *
 * Example:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 *     $saveButton = $this->componentFactory->createInputButton()
 *          ->setName('save')
 *          ->setValue('1')
 *          ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
 *          ->setTitle('Save');
 *
 *     $saveAndCloseButton = $this->componentFactory->createInputButton()
 *          ->setName('save_and_close')
 *          ->setValue('1')
 *          ->setTitle('Save and close')
 *          ->setIcon($this->iconFactory->getIcon('actions-document-save-close', IconSize::SMALL));
 *
 *     $saveAndShowPageButton = $this->componentFactory->createInputButton()
 *          ->setName('save_and_show')
 *          ->setValue('1')
 *          ->setTitle('Save and show')
 *          ->setIcon($this->iconFactory->getIcon('actions-document-save-view', IconSize::SMALL));
 *
 *     $moduleLink = $this->componentFactory->createLinkButton()
 *          ->setHref((string)$this->uriBuilder->buildUriFromRoute('file_edit', $parameter))
 *          ->setDataAttributes(['customAttribute' => 'customValue'])
 *          ->setShowLabelText(true)
 *          ->setTitle('Edit file')
 *          ->setIcon($this->iconFactory->getIcon('file-edit', IconSize::SMALL));
 *
 *     $splitButtonElement = $this->componentFactory->createSplitButton()
 *          ->addItem($saveButton, true)
 *          ->addItem($saveAndCloseButton)
 *          ->addItem($moduleLink)
 *          ->addItem($saveAndShowPageButton);
 * }
 * ```
 */
class SplitButton extends AbstractButton
{
    /**
     * Internal var that determines whether the split button has received any primary actions yet
     */
    protected bool $containsPrimaryAction = false;

    /**
     * Primary action button
     */
    protected ?AbstractButton $primary = null;

    /**
     * Array of option buttons for the dropdown
     *
     * @var AbstractButton[]
     */
    protected array $options = [];

    /**
     * Adds an instance of any button to the split button
     *
     * @param AbstractButton $item ButtonObject to add
     * @param bool $primaryAction Is the button the primary action?
     *
     * @throws \InvalidArgumentException In case a button is not valid
     */
    public function addItem(AbstractButton $item, bool $primaryAction = false): static
    {
        if (!$item->isValid()) {
            throw new \InvalidArgumentException(
                'Only valid items may be assigned to a split Button. "' .
                $item->getType() .
                '" did not pass validation',
                1441706330
            );
        }
        if ($primaryAction && $this->containsPrimaryAction) {
            throw new \InvalidArgumentException('A splitButton may only contain one primary action', 1441706340);
        }
        if ($primaryAction) {
            $this->containsPrimaryAction = true;
            $this->primary = clone $item;
        } else {
            $this->options[] = clone $item;
        }
        return $this;
    }

    /**
     * Returns the split button items as a typed DTO.
     *
     * If no primary action was explicitly set, the first option button
     * becomes the primary action.
     *
     * @return SplitButtonItems The typed container with primary and option buttons
     */
    public function getItems(): SplitButtonItems
    {
        $primary = $this->primary;
        $options = $this->options;

        // If no primary action was set, use the first option as primary or thrown an exception if no option exists
        if ($primary === null) {
            if (count($options) > 0) {
                $primary = array_shift($options);
            } else {
                throw new \RuntimeException('Split button requires at least one button', 1761311538);
            }
        }

        return new SplitButtonItems(primary: $primary, options: $options);
    }

    public function isValid(): bool
    {
        try {
            return $this->getItems()->isValid();
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Renders the HTML markup of the button
     */
    public function render(): string
    {
        $items = $this->getItems();
        $primary = $items->primary;
        $options = $items->options;

        $attributes = [
            'class' => 'btn btn-sm btn-default ' . $primary->getClasses(),
        ];
        if (method_exists($primary, 'getName')) {
            $attributes['name'] = $primary->getName();
        }
        if (method_exists($primary, 'getValue')) {
            $attributes['value'] = $primary->getValue();
        }
        if (method_exists($primary, 'getForm') && !empty($primary->getForm())) {
            $attributes['form'] = $primary->getForm();
        }
        if ($primary->getAttributes() !== []) {
            foreach ($primary->getAttributes() as $attributeName => $attributeValue) {
                $attributes[$attributeName] = $attributeValue;
            }
        }
        if ($primary->getDataAttributes() !== []) {
            foreach ($primary->getDataAttributes() as $attributeName => $attributeValue) {
                $attributes['data-' . $attributeName] = $attributeValue;
            }
        }

        if ($primary instanceof LinkButton) {
            // This is needed because the LinkButton can NOT use its ->render() method,
            // as we want to stick our icon in the result HTML.
            $attributes['href'] = $primary->getHref();
            $attributes['role'] = $primary->getRole();
            $attributes['title'] = $primary->getTitle();
        }

        $attributesString = GeneralUtility::implodeAttributes($attributes, true);

        if ($primary instanceof LinkButton) {
            $primaryButtonHTML = '<a ' . $attributesString . '>
            ' . ($primary->getIcon()?->render('inline') ?? '') . '
                ' . htmlspecialchars($primary->getTitle()) . '
            </a>';
        } else {
            $primaryButtonHTML = '<button ' . $attributesString . ' type="submit">
            ' . ($primary->getIcon()?->render('inline') ?? '') . '
                ' . htmlspecialchars($primary->getTitle()) . '
            </button>';
        }

        $content = '
        <div class="btn-group t3js-splitbutton">
            ' . $primaryButtonHTML . '
            <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">';

        foreach ($options as $option) {
            if ($option instanceof InputButton) {
                // if the option is an InputButton we have to create a custom rendering
                $optionAttributes = [
                    'href' => '#',
                    'data-name' => $option->getName(),
                    'data-value' => $option->getValue(),
                    'data-form' => $option->getForm(),
                ];

                if (!empty($option->getClasses())) {
                    $optionAttributes['class'] = $option->getClasses();
                }
                $optionAttributes['class'] = implode(' ', [$optionAttributes['class'] ?? '', 'dropdown-item']);
                $optionAttributesString = '';
                foreach ($optionAttributes as $key => $value) {
                    $optionAttributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
                }
                $html =
                    '<a' . $optionAttributesString . '>' .
                        '<span class="dropdown-item-columns">' .
                            '<span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">' .
                                ($option->getIcon()?->render('inline') ?? '') .
                            '</span>' .
                            '<span class="dropdown-item-column dropdown-item-column-title">' .
                                htmlspecialchars($option->getTitle()) .
                            '</span>' .
                        '</span>' .
                    '</a>';
            } else {
                // for any other kind of button we simply use what comes along (e.g. LinkButton)
                $html = $option->render();

                if ($option instanceof LinkButton) {
                    // Links inside a dropdown should not be displayed as a button.
                    // Unfortunately, the LinkButton has its class-list
                    // "btn btn-sm btn-default" hard-coded, which makes sense for
                    // its normal context, but not this context. Since it's hard via
                    // CSS to reset the "btn" look, we heuristically remove it here.
                    $html = str_replace('class="btn btn-sm btn-default', 'class="btn-sm btn-default dropdown-item', $html);
                }
            }

            $content .= '
                <li>
                   ' . $html . '
                </li>
            ';
        }
        $content .= '
            </ul>
        </div>
        ';
        return $content;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
