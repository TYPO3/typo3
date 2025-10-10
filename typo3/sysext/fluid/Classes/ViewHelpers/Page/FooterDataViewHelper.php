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

namespace TYPO3\CMS\Fluid\ViewHelpers\Page;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to add various content before the closing body-tag of the current page using PageRenderer.
 *
 * ```
 *    <f:page.footerData>
 *       <script>
 *         var _paq = window._paq = window._paq || [];
 *         _paq.push(['trackPageView']);
 *         _paq.push(['enableLinkTracking']);
 *         (function() {
 *             var u = "https://your-matomo-domain.example.com/";
 *             _paq.push(['setTrackerUrl', u + 'matomo.php']);
 *             _paq.push(['setSiteId', '1']);
 *             var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
 *           g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
 *         })();
 *       </script>
 *    </f:page.footerData>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-page-footerdata
 */
final class FooterDataViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly PageRenderer $pageRenderer) {}

    public function render(): string
    {
        $this->pageRenderer->addFooterData($this->renderChildren());
        return '';
    }
}
