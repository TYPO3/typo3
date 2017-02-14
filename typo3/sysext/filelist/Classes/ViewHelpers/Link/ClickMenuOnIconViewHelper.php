<?php
namespace TYPO3\CMS\Filelist\ViewHelpers\Link;

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
 * Class ClickMenuOnIconViewHelper
 */
class ClickMenuOnIconViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Defines additional arguments that are allowed for this view helper.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('table', 'string', '', true);
    }

    /**
     * Renders click menu link (context sensitive menu)
     *
     * @return string
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::wrapClickMenuOnIcon()
     */
    public function render()
    {
        $this->tag->addAttribute('class', 't3js-contextmenutrigger ' . $this->arguments['class']);
        $this->tag->addAttribute('data-uid', $this->arguments['table']);
        $this->tag->addAttribute('data-table', 'sys_file');
        $this->tag->addAttribute('href', '#');

        $this->tag->setContent($this->renderChildren());

        return $this->tag->render();
    }
}
