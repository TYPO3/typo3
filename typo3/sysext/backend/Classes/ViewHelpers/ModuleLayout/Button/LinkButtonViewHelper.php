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

namespace TYPO3\CMS\Backend\ViewHelpers\ModuleLayout\Button;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A ViewHelper for adding a link button to the doc header area.
 * It must be a child of :ref:`<be:moduleLayout> <typo3-backend-modulelayout>`.
 *
 * Examples
 * --------
 *
 * Default::
 *
 *    <be:moduleLayout>
 *        <be:moduleLayout.button.linkButton
 *            icon="actions-add"
 *            title="Add record')}"
 *            link="{be:uri.newRecord(table: 'tx_my_table')}"
 *        />
 *    </be:moduleLayout>
 *
 * @deprecated since TYPO3 v11.3, will be removed in TYPO3 v12.0. Deprecation logged AbstractButtonViewHelper.
 */
class LinkButtonViewHelper extends AbstractButtonViewHelper
{
    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('link', 'string', 'Link for the button', true);
    }

    protected static function createButton(ButtonBar $buttonBar, array $arguments, RenderingContextInterface $renderingContext): ButtonInterface
    {
        return $buttonBar->makeLinkButton()->setHref($arguments['link']);
    }
}
