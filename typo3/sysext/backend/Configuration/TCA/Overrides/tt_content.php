<?php

defined('TYPO3') or die();

// Activate codeEditor for tt_content type HTML if this type exists
if (is_array($GLOBALS['TCA']['tt_content']['types']['html'] ?? null)) {
    $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides'] = array_replace_recursive(
        $GLOBALS['TCA']['tt_content']['types']['html']['columnsOverrides'] ?? [],
        [
            'bodytext' => [
                'config' => [
                    'renderType' => 'codeEditor',
                    'wrap' => 'off',
                    'format' => 'html',
                ],
            ],
        ]
    );
}
