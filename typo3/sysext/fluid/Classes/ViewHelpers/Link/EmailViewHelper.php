<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

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
 * Email link view helper.
 * Generates an email link incorporating TYPO3s spamProtectEmailAddresses-settings.
 *
 * = Examples
 *
 * <code title="basic email link">
 * <f:link.email email="foo@bar.tld" />
 * </code>
 * <output>
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">foo(at)bar.tld</a>
 * (depending on your spamProtectEmailAddresses-settings)
 * </output>
 *
 * <code title="Email link with custom linktext">
 * <f:link.email email="foo@bar.tld">some custom content</f:link.email>
 * </code>
 * <output>
 * <a href="javascript:linkTo_UnCryptMailto('ocknvq,hqqBdct0vnf');">some custom content</a>
 * </output>
 */
class EmailViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('email', 'string', 'The email address to be turned into a link', true);
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    /**
     * @return string Rendered email link
     */
    public function render()
    {
        $email = $this->arguments['email'];

        if ($this->isFrontendAvailable()) {
            list($linkHref, $linkText) = $GLOBALS['TSFE']->cObj->getMailTo($email, '');
            $escapeSpecialCharacters = !isset($GLOBALS['TSFE']->spamProtectEmailAddresses) || $GLOBALS['TSFE']->spamProtectEmailAddresses !== 'ascii';
        } else {
            $linkHref = 'mailto:' . $email;
            $linkText = htmlspecialchars($email);
            $escapeSpecialCharacters = true;
        }
        $tagContent = $this->renderChildren();
        if ($tagContent !== null) {
            $linkText = $tagContent;
        }
        $this->tag->setContent($linkText);
        $this->tag->addAttribute('href', $linkHref, $escapeSpecialCharacters);
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    /**
     * @return bool
     */
    protected function isFrontendAvailable()
    {
        return TYPO3_MODE === 'FE';
    }
}
