<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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
        $email = $arguments['email'];

        if (TYPO3_MODE === 'FE') {
            $emailParts = $GLOBALS['TSFE']->cObj->getMailTo($email, $email);
            return reset($emailParts);
        }
        return 'mailto:' . $email;
    }
}
