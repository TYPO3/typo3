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

namespace TYPO3\CMS\Backend\ViewHelpers\Link;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Use this ViewHelper to provide a link to the official documentation. The ViewHelper will
 * use the permalink identifier to generate a permalink to the documentation which is
 * a redirect to the actual URI.
 *
 * The identifier must be given as a string. Be aware that very specific short links into
 * the documentation may change over time.
 *
 * The link will always lead to the documentation of the corresponding TYPO3 version. This
 * means in a v12 installation, using `foo-bar` as identifier will link to 'foo-bar@12.4',
 * while in v13 the link will be 'foo-bar@13.4'.
 *
 * Example
 * =======
 *
 * Link to the documentation::
 *
 *    <be:link.documentation identifier="foo-bar">See documentation</be:link.documentation>
 *
 * Output::
 *
 *    <a href="https://docs.typo3.org/permalink/foo-bar@13.4" target="_blank" rel="noreferrer">
 *        See documentation
 *    </a>
 *
 * @internal not part of TYPO3 Core API.
 */
final class DocumentationViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    protected string $urlPattern = 'https://docs.typo3.org/permalink/%s@%s';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('identifier', 'string', 'the documentation permalink identifier as displayed in the modal link popup of any rendered documentation manual', true);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function render(): string
    {
        if (str_contains($this->arguments['identifier'], '@')) {
            throw new \InvalidArgumentException('The identifier must not contain the "@" character.', 1728643940);
        }
        $this->tag->addAttribute('href', sprintf($this->urlPattern, $this->arguments['identifier'], (new Typo3Version())->getBranch()));
        $this->tag->addAttribute('target', '_blank');
        $this->tag->addAttribute('rel', 'noreferrer');
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
