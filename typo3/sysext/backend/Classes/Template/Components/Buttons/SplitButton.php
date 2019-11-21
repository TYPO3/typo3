<?php
namespace TYPO3\CMS\Backend\Template\Components\Buttons;

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

/**
 * SplitButton
 *
 * This button type renders a bootstrap split button.
 * It takes multiple button objects as parameters
 *
 * EXAMPLE USAGE TO ADD A SPLIT BUTTON TO THE FIRST BUTTON GROUP IN THE LEFT BAR:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *
 * $saveButton = $buttonBar->makeInputButton()
 *      ->setName('save')
 *      ->setValue('1')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
 *      ->setTitle('Save');
 *
 * $saveAndCloseButton = $buttonBar->makeInputButton()
 *      ->setName('save_and_close')
 *      ->setValue('1')
 *      ->setTitle('Save and close')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));
 *
 * $saveAndShowPageButton = $buttonBar->makeInputButton()
 *      ->setName('save_and_show')
 *      ->setValue('1')
 *      ->setTitle('Save and show')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save-view', Icon::SIZE_SMALL));
 *
 * $splitButtonElement = $buttonBar->makeSplitButton()
 *      ->addItem($saveButton, TRUE)
 *      ->addItem($saveAndCloseButton)
 *      ->addItem($saveAndShowPageButton);
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
            'type' => 'submit',
            'class' => 'btn btn-sm btn-default ' . $items['primary']->getClasses(),
        ];
        if (method_exists($items['primary'], 'getName')) {
            $attributes['name'] = $items['primary']->getName();
        }
        if (method_exists($items['primary'], 'getValue')) {
            $attributes['value'] = $items['primary']->getValue();
        }
        if (!empty($items['primary']->getOnClick())) {
            $attributes['onclick'] = $items['primary']->getOnClick();
        }
        if (method_exists($items['primary'], 'getForm') && !empty($items['primary']->getForm())) {
            $attributes['form'] = $items['primary']->getForm();
        }
        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        $content = '
        <div class="btn-group t3js-splitbutton">
            <button' . $attributesString . '>
                ' . $items['primary']->getIcon()->render('inline') . '
                ' . htmlspecialchars($items['primary']->getTitle()) . '
            </button>
            <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
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
                    'data-form' => $option->getForm()
                ];

                if (!empty($option->getClasses())) {
                    $optionAttributes['class'] = $option->getClasses();
                }
                if (!empty($option->getOnClick())) {
                    $optionAttributes['onclick'] = $option->getOnClick();
                }
                $optionAttributesString = '';
                foreach ($optionAttributes as $key => $value) {
                    $optionAttributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
                }
                $html =  '<a' . $optionAttributesString . '>' . $option->getIcon()->render('inline') . ' '
                    . htmlspecialchars($option->getTitle()) . '</a>';
            } else {
                // for any other kind of button we simply use what comes along (e.g. LinkButton)
                $html = $option->render();
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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
