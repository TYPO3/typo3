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

namespace TYPO3\CMS\Backend\ViewHelpers\Toolbar;

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to build a "class" attribute string for use in rendered toolbar items.
 *
 * ```
 *  <be:toolbar.attributes class="{someToolbarItemInterfaceInstance}" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-toolbar-attributes
 * @internal
 */
final class AttributesViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('class', ToolbarItemInterface::class, 'Class being converted to a string for usage as id attribute', true);
    }

    public function render(): string
    {
        $additionalAttributes = [
            'class' => 'toolbar-item',
        ];
        $toolbarItem = $this->arguments['class'] ?? null;
        if ($toolbarItem instanceof ToolbarItemInterface) {
            $additionalAttributes['class'] .= ' ' . ($toolbarItem->getAdditionalAttributes()['class'] ?? '');
            $additionalAttributes['id'] = self::convertClassNameToIdAttribute(\get_class($toolbarItem));
        }
        return GeneralUtility::implodeAttributes($additionalAttributes);
    }

    private static function convertClassNameToIdAttribute(string $fullyQualifiedClassName): string
    {
        $className = GeneralUtility::underscoredToLowerCamelCase($fullyQualifiedClassName);
        $className = GeneralUtility::camelCaseToLowerCaseUnderscored($className);

        return str_replace(['_', '\\'], '-', $className);
    }
}
