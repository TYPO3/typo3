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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Email URI view helper.
 * Generates an email URI incorporating TYPO3s spamProtectEmailAddresses-settings.
 *
 * = Examples
 *
 * <code title="basic email URI">
 * <f:uri.email email="foo@bar.tld" />
 * </code>
 * <output>
 * javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');
 * (depending on your spamProtectEmailAddresses-settings)
 * </output>
 */
class EmailViewHelper extends AbstractViewHelper
{
    /**
     * @param string $email The email address to be turned into a URI
     * @return string Rendered email link
     */
    public function render($email)
    {
        return static::renderStatic(
            array(
                'email' => $email
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $email = $arguments['email'];

        if (TYPO3_MODE === 'FE') {
            $emailParts = $GLOBALS['TSFE']->cObj->getMailTo($email, $email);
            return reset($emailParts);
        } else {
            return 'mailto:' . $email;
        }
    }
}
