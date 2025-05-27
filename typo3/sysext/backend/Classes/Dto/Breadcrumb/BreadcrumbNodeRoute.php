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

namespace TYPO3\CMS\Backend\Dto\Breadcrumb;

/**
 * Represents routing information for a breadcrumb node.
 *
 * Defines where clicking on a breadcrumb node should navigate to, including
 * the target module and any required parameters.
 *
 * @internal Subject to change until v15 LTS
 */
final readonly class BreadcrumbNodeRoute implements \JsonSerializable
{
    /**
     * @param string $module Backend module identifier (e.g., 'web_layout', 'web_info', 'media_management')
     * @param array $params Additional parameters passed to the module (e.g., ['id' => '42'] for page ID, ['id' => '1:/images/'] for storage path)
     */
    public function __construct(
        public string $module,
        public array $params,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
