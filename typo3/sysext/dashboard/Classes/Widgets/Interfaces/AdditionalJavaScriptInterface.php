<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

/**
 * Interface AdditionalJavaScriptInterface
 * In case a widget should provide additional JavaScript files, the widget must be implemented.
 */
interface AdditionalJavaScriptInterface
{
    /**
     * This method returns an array with paths to required JS files.
     * e.g. ['EXT:myext/Resources/Public/JavaScript/my_widget.js']
     * @return array
     */
    public function getJsFiles(): array;
}
