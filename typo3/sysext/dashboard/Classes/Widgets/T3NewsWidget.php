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

/**
 * This class will make it possible for users to show the latest news from the TYPO3 news RSS feed right on the
 * dashboard.
 */
class T3NewsWidget extends AbstractRssWidget
{
    protected $rssFile = 'https://www.typo3.org/rss';
    protected $lifeTime = 43200; // 12 hours cache
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title';
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description';
    protected $iconIdentifier = 'dashboard-typo3';
    protected $moreItemsLink = 'https://typo3.org/project/news';
    protected $moreItemsText = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems';
}
