<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Widgets;

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

use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * This widget will show general information regarding TYPO3
 */
class T3GeneralInformation extends AbstractWidget
{
    /**
     * @var string
     */
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3information.title';

    /**
     * @var string
     */
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3information.description';

    /**
     * @var string
     */
    protected $templateName = 'T3GeneralInformation';

    /**
     * @var string
     */
    protected $iconIdentifier = 'content-widget-text';

    /**
     * @var int
     */
    protected $height = 4;

    /**
     * @var int
     */
    protected $width = 4;

    public function renderWidgetContent(): string
    {
        $typo3Information = new Typo3Information();
        $typo3Version = new Typo3Version();
        $this->view->assignMultiple([
            'title' => 'TYPO3 CMS ' . $typo3Version->getVersion(),
            'copyrightYear' => $typo3Information->getCopyrightYear(),
            'currentVersion' => $typo3Version->getVersion(),
            'donationUrl' => $typo3Information::URL_DONATE,
            'copyRightNotice' => $typo3Information->getCopyrightNotice(),

        ]);
        return $this->view->render();
    }
}
