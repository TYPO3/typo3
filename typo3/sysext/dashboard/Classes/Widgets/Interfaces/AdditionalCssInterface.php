<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

/**
 * Interface AdditionalCssInterface
 * In case a widget should provide additional CSS files, the widget must implement this interface.
 */
interface AdditionalCssInterface
{
    /**
     * This method returns an array with paths to required CSS files.
     * e.g. ['EXT:myext/Resources/Public/Css/my_widget.css']
     * @return array
     */
    public function getCssFiles(): array;
}
