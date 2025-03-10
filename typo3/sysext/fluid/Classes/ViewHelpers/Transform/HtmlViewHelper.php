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

namespace TYPO3\CMS\Fluid\ViewHelpers\Transform;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Html\HtmlWorker;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to transform HTML and substitute internal link scheme aspects.
 *
 * ```
 *   <f:transform.html selector="a.href" onFailure="removeEnclosure">
 *       <a href="t3://page?uid=1" class="home">Home</a>
 *   </f:transform.html>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-transform-html
 */
final class HtmlViewHelper extends AbstractViewHelper
{
    protected const MAP_ON_FAILURE = [
        '' => 0,
        'null' => 0,
        'removeTag' => HtmlWorker::REMOVE_TAG_ON_FAILURE,
        'removeAttr' => HtmlWorker::REMOVE_ATTR_ON_FAILURE,
        'removeEnclosure' => HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE,
    ];

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('selector', 'string', 'comma separated list of node attributes to be considered', false, 'a.href');
        $this->registerArgument('onFailure', 'string', 'behavior on failure, either `removeTag`, `removeAttr`, `removeEnclosure` or `null`', false, 'removeEnclosure');
    }

    /**
     * @return string transformed markup
     */
    public function render(): string
    {
        $content = $this->renderChildren();
        /** @var HtmlWorker $worker */
        $worker = GeneralUtility::makeInstance(HtmlWorker::class);
        $selector = $this->arguments['selector'];
        $onFailure = $this->arguments['onFailure'];
        $onFailureFlags = self::MAP_ON_FAILURE[$onFailure] ?? HtmlWorker::REMOVE_ENCLOSURE_ON_FAILURE;
        return (string)$worker
            ->parse((string)$content)
            ->transformUri($selector, $onFailureFlags);
    }
}
