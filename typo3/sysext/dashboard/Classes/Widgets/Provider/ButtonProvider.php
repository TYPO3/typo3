<?php
declare(strict_types=1);
namespace TYPO3\CMS\Dashboard\Widgets\Provider;

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

use TYPO3\CMS\Dashboard\Widgets\Interfaces\ButtonProviderInterface;

/**
 * Provide link for sys log button.
 * Check whether belog is enabled and add link to module.
 * No link is returned if not enabled.
 */
class ButtonProvider implements ButtonProviderInterface
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $link;

    public function __construct(string $title, string $link, string $target = '')
    {
        $this->title = $title;
        $this->target = $target;
        $this->link = $link;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
