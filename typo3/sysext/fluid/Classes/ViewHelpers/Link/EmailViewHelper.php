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

namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\LinkHandling\EmailLinkHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Email link ViewHelper.
 * Generates an email link incorporating TYPO3s `spamProtectEmailAddresses`_ TypoScript setting.
 *
 * .. _spamProtectEmailAddresses: https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Setup/Config/Index.html#spamprotectemailaddresses
 *
 * Examples
 * ========
 *
 * Basic email link
 * ----------------
 *
 * ::
 *
 *    <f:link.email email="foo@bar.tld" />
 *
 * Output::
 *
 *    <a href="#" data-mailto-token="ocknvq,hqqBdct0vnf" data-mailto-vector="1">foo(at)bar.tld</a>
 *
 * Depending on `spamProtectEmailAddresses`_ setting.
 *
 * Email link with custom linktext
 * -------------------------------
 *
 * ::
 *
 *    <f:link.email email="foo@bar.tld">some custom content</f:link.email>
 *
 * Output::
 *
 *
 * Email link with custom subject and prefilled cc
 * -----------------------------------------------
 *
 * ::
 *
 *    <f:link.email email="foo@bar.tld" subject="Check out this website" cc="foo@example.com"">some custom content</f:link.email>
 *
 * Output::
 *
 *    <a href="mailto:foo@bar.tld?subject=Check%20out%20this%20website&amp;cc=foo%40example.com">some custom content</a>
 *
 * Depending on `spamProtectEmailAddresses`_ setting.
 */
final class EmailViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('email', 'string', 'The email address to be turned into a link', true);
        $this->registerArgument('cc', 'string', 'The email address(es) for CC of the email link');
        $this->registerArgument('bcc', 'string', 'The email address(es) for BCC of the email link');
        $this->registerArgument('subject', 'string', 'A prefilled subject for the email link');
        $this->registerArgument('body', 'string', 'A prefilled body for the email link');
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    public function render(): string
    {
        $email = $this->arguments['email'];
        $linkHref = GeneralUtility::makeInstance(EmailLinkHandler::class)->asString($this->arguments);
        $attributes = [];
        $linkText = htmlspecialchars($email);
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();
        if ($request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isFrontend()) {
            // If there is no request, backend is assumed.
            /** @var TypoScriptFrontendController $frontend */
            $frontend = $GLOBALS['TSFE'];
            // passing HTML encoded link text
            try {
                $linkResult = GeneralUtility::makeInstance(LinkFactory::class)->create($linkText, ['parameter' => $linkHref], $frontend->cObj);
                $linkText = (string)$linkResult->getLinkText();
                $attributes = $linkResult->getAttributes();
            } catch (UnableToLinkException $e) {
                // Just render the email as is (= Backend Context), if LinkBuilder failed
            }
        }
        $tagContent = $this->renderChildren();
        if ($tagContent !== null) {
            $linkText = $tagContent;
        }
        $this->tag->setContent($linkText);
        $this->tag->addAttribute('href', $linkHref);
        $this->tag->forceClosingTag(true);
        $this->tag->addAttributes($attributes);
        return $this->tag->render();
    }
}
