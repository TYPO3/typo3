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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper to generate an email link (`mailto:`), respecting TYPO3s `spamProtectEmailAddresses` TypoScript setting.
 *
 * ```
 *   <f:link.email email="foo@example.com" subject="Website contact" cc="fooSupervisor@example.com" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-link-email
 * @see https://docs.typo3.org/permalink/t3tsref:confval-config-spamprotectemailaddresses
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
    }

    public function render(): string
    {
        $email = $this->arguments['email'];
        $linkHref = GeneralUtility::makeInstance(EmailLinkHandler::class)->asString($this->arguments);
        $attributes = [];
        $linkText = htmlspecialchars($email);
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)
            && ApplicationType::fromRequest($this->renderingContext->getAttribute(ServerRequestInterface::class))->isFrontend()
        ) {
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
            $linkText = (string)$tagContent;
        }
        $this->tag->setContent($linkText);
        $this->tag->addAttribute('href', $linkHref);
        $this->tag->forceClosingTag(true);
        $this->tag->addAttributes($attributes);
        return $this->tag->render();
    }
}
