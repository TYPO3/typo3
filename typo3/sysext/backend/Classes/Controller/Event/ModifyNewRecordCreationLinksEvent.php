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

namespace TYPO3\CMS\Backend\Controller\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Event to modify the grouped links as result of NewRecordController
 *
 * Structure (array):
 *
 * "content" => [
 *     "title" => "Content",
 *     "icon" => "<img...>"
 *     "items" => [
 *         "sys_note" => [
 *             [
 *                 "url" => "...",
 *                 "icon" => "...",
 *                 "label" => "...",
 *             ],
 *         ],
 *         "sys_file_collection" => [
 *             [
 *                 "icon" => "...",
 *                 "label" => "...",
 *                 "types" => [
 *                     "static" => [
 *                         'url' => "...",
 *                         'icon' => "...",
 *                         'label' => "...",
 *                     ],
 *                     "folder" => [
 *                          'url' => "...",
 *                          'icon' => "...",
 *                          'label' => "...",
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 * "system" => [
 *     "title" => "System Records",
 *     "icon" => "<img...>"
 *     "items" => [
 *         "sys_template" => [
 *             [
 *                 "url" => "...",
 *                 "icon" => "...",
 *                 "label" => "...",
 *             ],
 *         ],
 *         "backend_layout" => [
 *             [
 *                 "url" => "...",
 *                 "icon" => "...",
 *                 "label" => "...",
 *             ],
 *         ],
 *     ],
 * ],
 */
final class ModifyNewRecordCreationLinksEvent
{
    public function __construct(
        public array $groupedCreationLinks,
        public readonly array $pageTS,
        public readonly int $pageId,
        public readonly ServerRequestInterface $request
    ) {}
}
