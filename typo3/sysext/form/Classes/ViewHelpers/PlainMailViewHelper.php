<?php
namespace TYPO3\CMS\Form\ViewHelpers;

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
 * A viewhelper for the plain mail view
 */
class PlainMailViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Render the plain mail view
     *
     * @param mixed $labelContent
     * @param mixed $content
     * @param bool $newLineAfterLabel
     * @param int $indent
     * @return string
     */
    public function render($labelContent = null, $content = null, $newLineAfterLabel = false, $indent = 0)
    {
        $templateVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$templateVariableContainer->exists(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'spaces')) {
            $templateVariableContainer->add(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'spaces', 0);
        }

        $spaces = $templateVariableContainer->get(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'spaces');
        $output = '';
        if ($labelContent) {
            if ($labelContent instanceof \TYPO3\CMS\Form\Domain\Model\Element) {
                $output = $this->getLabel($labelContent);
            } else {
                $output = $labelContent;
            }
            if ($newLineAfterLabel) {
                if ($output !== '') {
                    $output = str_repeat(chr(32), $spaces) . $output . LF;
                }
                $this->setIndent($indent);
            }
        }

        if ($content) {
            if (!$newLineAfterLabel) {
                $this->setIndent($indent);
            }
            if (
                $labelContent
                && !$newLineAfterLabel
            ) {
                $output = $output . ': ' . $this->getValue($content);
            } elseif (
                $labelContent
                && $newLineAfterLabel
            ) {
                $output =
                    $output .
                    str_repeat(chr(32), ($spaces + 4)) .
                    str_replace(LF, LF . str_repeat(chr(32), ($spaces + 4)), $this->getValue($content));
            } else {
                $output = $this->getValue($content);
            }
        }

        if (
            $labelContent
            || $content
        ) {
            if (
                $output !== ''
                && !$newLineAfterLabel
            ) {
                $output = str_repeat(chr(32), $spaces) . $output;
            }
        } else {
            $this->setIndent($indent);
        }

        return $output;
    }

    /**
     * Get the label
     * @param \TYPO3\CMS\Form\Domain\Model\Element $model
     * @return string
     */
    protected function getLabel(\TYPO3\CMS\Form\Domain\Model\Element $model)
    {
        $label = '';
        if ($model->getAdditionalArgument('legend')) {
            $label = $model->getAdditionalArgument('legend');
        } elseif ($model->getAdditionalArgument('label')) {
            $label = $model->getAdditionalArgument('label');
        }
        return $label;
    }

    /**
     * Get the label
     * @param mixed $model
     * @return string
     */
    protected function getValue($content)
    {
        $value = '';
        if ($content instanceof \TYPO3\CMS\Form\Domain\Model\Element) {
            $value = $content->getAdditionalArgument('value');
        } else {
            $value = $content;
        }
        return $value;
    }

    /**
     * Set the current indent level
     *
     * @param int $indent
     * @return void
     */
    public function setIndent($indent = 0)
    {
        $templateVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        $spaces = $templateVariableContainer->get(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'spaces');
        $spaces += (int)$indent;
        $templateVariableContainer->addOrUpdate(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'indent', $indent);
        $templateVariableContainer->addOrUpdate(\TYPO3\CMS\Form\ViewHelpers\PlainMailViewHelper::class, 'spaces', $spaces);
    }
}
