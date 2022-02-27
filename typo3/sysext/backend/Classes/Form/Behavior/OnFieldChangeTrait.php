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

namespace TYPO3\CMS\Backend\Form\Behavior;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait OnFieldChangeTrait
{
    /**
     * @param list<OnFieldChangeInterface> $items `fieldChangeFunc` items
     * @return array<int, array>
     */
    protected function getOnFieldChangeItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }
        return array_map(
            static function (OnFieldChangeInterface $item) {
                return $item->toArray();
            },
            // omitting array keys
            array_values($items)
        );
    }

    /**
     * @param string $event target client event, either `change` or `click`
     * @param list<OnFieldChangeInterface> $items `fieldChangeFunc` items
     * @return array<string, string> HTML attrs, not encoded - consumers MUST encode with `htmlspecialchars`
     */
    protected function getOnFieldChangeAttrs(string $event, array $items): array
    {
        if (empty($items)) {
            return [];
        }
        $onFieldChangeItems = $this->getOnFieldChangeItems($items);
        return [
            'data-formengine-field-change-event' => $event,
            'data-formengine-field-change-items' => GeneralUtility::jsonEncodeForHtmlAttribute($onFieldChangeItems, false),
        ];
    }

    /**
     * Forwards URL query params for `LinkBrowserController`
     * @param list<OnFieldChangeInterface> $items `fieldChangeFunc` items
     * @return array{fieldChangeFunc: array<int, array>, fieldChangeFuncHash: string} relevant URL query params for `LinkBrowserController`
     */
    protected function forwardOnFieldChangeQueryParams(array $items): array
    {
        $func = $this->getOnFieldChangeItems($items);
        return [
            'fieldChangeFunc' => $func,
            'fieldChangeFuncHash' => GeneralUtility::hmac(serialize($func), 'backend-link-browser'),
        ];
    }
}
