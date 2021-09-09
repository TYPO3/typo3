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

namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Email URI ViewHelper.
 * Generates an email URI incorporating TYPO3s `spamProtectEmailAddresses`_ TypoScript setting.
 *
 * .. _spamProtectEmailAddresses: https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Setup/Config/Index.html#spamprotectemailaddresses
 *
 * Example
 * =======
 *
 * Basic email URI::
 *
 *    <f:uri.email email="foo@bar.tld" />
 *
 * Output::
 *
 *    javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');
 *
 * Depending on `spamProtectEmailAddresses`_ setting.
 *
 * @deprecated Will be removed in TYPO3 v12.0
 */
class EmailViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('email', 'string', 'The email address to be turned into a URI', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string Rendered email link
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        trigger_error('f:uri.email view-helper is deprecated an will be removed in TYPO3 v12.0', E_USER_DEPRECATED);

        $email = $arguments['email'];
        if (ApplicationType::fromRequest($renderingContext->getRequest())->isFrontend()) {
            /** @var TypoScriptFrontendController $frontend */
            $frontend = $GLOBALS['TSFE'];
            [$linkHref, $linkText, $attributes] = $frontend->cObj->getMailTo($email, $email);
            if (isset($attributes['data-mailto-token']) && isset($attributes['data-mailto-vector'])) {
                $linkHref = sprintf(
                    'javascript:linkTo_UnCryptMailto(%s,%d);',
                    rawurlencode(GeneralUtility::quoteJSvalue($attributes['data-mailto-token'])),
                    -(int)$attributes['data-mailto-vector']
                );
            }
            return $linkHref;
        }
        return 'mailto:' . $email;
    }
}
