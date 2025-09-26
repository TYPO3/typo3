<?php

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

/**
 * SplitButton
 *
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
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 * $saveButton = $buttonBar->makeInputButton()
 *      ->setName('save')
 *      ->setValue('1')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
 *      ->setTitle('Save');
 *
 * $saveAndCloseButton = $buttonBar->makeInputButton()
 *      ->setName('save_and_close')
 *      ->setValue('1')
 *      ->setTitle('Save and close')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save-close', IconSize::SMALL));
 *
 * $saveAndShowPageButton = $buttonBar->makeInputButton()
 *      ->setName('save_and_show')
 *      ->setValue('1')
 *      ->setTitle('Save and show')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save-view', IconSize::SMALL));
 *
 * $moduleLink = $buttonBar->makeLinkButton()
 *      ->setHref((string)$this->uriBuilder->buildUriFromRoute('file_edit', $parameter))
 *      ->setDataAttributes(['customAttribute' => 'customValue'])
 *      ->setShowLabelText(true)
 *      ->setTitle('Edit file')
 *      ->setIcon($this->iconFactory->getIcon('file-edit', IconSize::SMALL));
 *
 * $splitButtonElement = $buttonBar->makeSplitButton()
 *      ->addItem($saveButton, true)
 *      ->addItem($saveAndCloseButton)
 *      ->addItem($moduleLink)
 *      ->addItem($saveAndShowPageButton);
 * ```
 */
class SplitButton extends AbstractButton
{
    /**
     * Internal var that determines whether the split button has received any primary
     * actions yet
     *
     * @var bool
     */
    protected $containsPrimaryAction = false;

    /**
     * Internal array of items in the split button
     *
     * @var array
     */
    protected $items = [];

    /**
     * Adds an instance of any button to the split button
     *
     * @param AbstractButton $item ButtonObject to add
     * @param bool $primaryAction Is the button the primary action?
     *
     * @throws \InvalidArgumentException In case a button is not valid
     *
     * @return $this
     */
    public function addItem(AbstractButton $item, $primaryAction = false)
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
            $this->items['primary'] = clone $item;
        } else {
            $this->items['options'][] = clone $item;
        }
        return $this;
    }

    /**
     * Returns the current button
     *
     * @return array
     */
    public function getButton()
    {
        if (!isset($this->items['primary']) && isset($this->items['options'])) {
            $primaryAction = array_shift($this->items['options']);
            $this->items['primary'] = $primaryAction;
        }
        return $this->items;
    }

    /**
     * Validates the current button
     *
     *
     * @return bool
     */
    public function isValid()
    {
        $subject = $this->getButton();
        return isset($subject['primary'])
               && ($subject['primary'] instanceof AbstractButton)
               && isset($subject['options']);
    }

    /**
     * Renders the HTML markup of the button
     *
     * @return string
     */
    public function render()
    {
        $items = $this->getButton();
        $attributes = [
            'class' => 'btn btn-sm btn-default ' . $items['primary']->getClasses(),
        ];
        if (method_exists($items['primary'], 'getName')) {
            $attributes['name'] = $items['primary']->getName();
        }
        if (method_exists($items['primary'], 'getValue')) {
            $attributes['value'] = $items['primary']->getValue();
        }
        if (method_exists($items['primary'], 'getForm') && !empty($items['primary']->getForm())) {
            $attributes['form'] = $items['primary']->getForm();
        }
        if (method_exists($items['primary'], 'getDataAttributes') && !empty($items['primary']->getDataAttributes())) {
            foreach ($items['primary']->getDataAttributes() as $attributeName => $attributeValue) {
                $attributes['data-' . $attributeName] = $attributeValue;
            }
        }

        if ($items['primary'] instanceof LinkButton) {
            // This is needed because the LinkButton can NOT use its ->render() method,
            // as we want to stick our icon in the result HTML.
            $attributes['href'] = $items['primary']->getHref();
            $attributes['role'] = $items['primary']->getRole();
            $attributes['title'] = $items['primary']->getTitle();
        }

        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }

        if ($items['primary'] instanceof LinkButton) {
            $primaryButtonHTML = '<a ' . $attributesString . '>
            ' . $items['primary']->getIcon()->render('inline') . '
                ' . htmlspecialchars($items['primary']->getTitle()) . '
            </a>';
        } else {
            $primaryButtonHTML = '<button' . $attributesString . ' type="submit">
            ' . $items['primary']->getIcon()->render('inline') . '
                ' . htmlspecialchars($items['primary']->getTitle()) . '
            </button>';
        }

        $content = '
        <div class="btn-group t3js-splitbutton">
            ' . $primaryButtonHTML . '
            <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu">';

        /** @var AbstractButton $option */
        foreach ($items['options'] as $option) {
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
                $html = '' .
                    '<a' . $optionAttributesString . '>' .
                        '<span class="dropdown-item-columns">' .
                            '<span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">' .
                                $option->getIcon()->render('inline') .
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

    /**
     * Magic method so Fluid can access a button via {button}
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
