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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Model\Element;

/**
 * A viewhelper for the plain mail view
 */
class PlainMailViewHelper extends AbstractViewHelper
{
    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('labelContent', 'mixed', '');
        $this->registerArgument('content', 'mixed', '');
        $this->registerArgument('newLineAfterLabel', 'bool', '', false, false);
        $this->registerArgument('indent', 'int', '', false, 0);
    }

    /**
     * Render the plain mail view
     *
     * @return string
     */
    public function render()
    {
        $templateVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$templateVariableContainer->exists(__CLASS__, 'spaces')) {
            $templateVariableContainer->add(__CLASS__, 'spaces', 0);
        }

        $spaces = $templateVariableContainer->get(__CLASS__, 'spaces');
        $output = '';
        if ($this->arguments['labelContent']) {
            if ($this->arguments['labelContent'] instanceof Element) {
                $output = $this->getLabel($this->arguments['labelContent']);
            } else {
                $output = $this->arguments['labelContent'];
            }
            if ($this->arguments['newLineAfterLabel']) {
                if ($output !== '') {
                    $output = str_repeat(chr(32), $spaces) . $output . LF;
                }
                $this->setIndent($this->arguments['indent']);
            }
        }

        if ($this->arguments['content']) {
            if (!$this->arguments['newLineAfterLabel']) {
                $this->setIndent($this->arguments['indent']);
            }
            if ($this->arguments['labelContent'] && !$this->arguments['newLineAfterLabel']) {
                $output = $output . ': ' . $this->getValue($this->arguments['content']);
            } elseif ($this->arguments['labelContent'] && $this->arguments['newLineAfterLabel']) {
                $output = $output
                    . str_repeat(chr(32), ($spaces + 4))
                    . str_replace(LF, LF . str_repeat(chr(32), ($spaces + 4)), $this->getValue($this->arguments['content']));
            } else {
                $output = $this->getValue($this->arguments['content']);
            }
        }

        if ($this->arguments['labelContent'] || $this->arguments['content']) {
            if ($output !== '' && !$this->arguments['newLineAfterLabel']) {
                $output = str_repeat(chr(32), $spaces) . $output;
            }
        } else {
            $this->setIndent($this->arguments['indent']);
        }

        return $output;
    }

    /**
     * Get the label
     * @param Element $model
     * @return string
     */
    protected function getLabel(Element $model)
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
     *
     * @param string $content
     * @return string
     */
    protected function getValue($content)
    {
        return $content instanceof Element ? $content->getAdditionalArgument('value') : $content;
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
        $spaces = $templateVariableContainer->get(__CLASS__, 'spaces');
        $spaces += (int)$indent;
        $templateVariableContainer->addOrUpdate(__CLASS__, 'indent', $indent);
        $templateVariableContainer->addOrUpdate(__CLASS__, 'spaces', $spaces);
    }
}
