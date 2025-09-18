<?php

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

$fileMtimeActions = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/actions.svg');
return <<<EOF
<button
    data-dispatch-action="TYPO3.ShortcutMenu.createShortcut"
    data-dispatch-args="[&amp;quot;web_list&amp;quot;,&amp;quot;{\u0022id\u0022:123,\u0022table\u0022:\u0022some_table\u0022,\u0022GET\u0022:{\u0022clipBoard\u0022:1}}&amp;quot;,&amp;quot;Recordlist - single table view&amp;quot;,&amp;quot;Create a bookmark to this record&amp;quot;,&amp;quot;{\$target}&amp;quot;]"
    class="btn btn-sm btn-default"
    title="Create a bookmark to this record">
    <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-system-shortcut-new" data-identifier="actions-system-shortcut-new" aria-hidden="true">
        <span class="icon-markup">
            <svg class="icon-color">
                <use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?{$fileMtimeActions}#actions-star" />
            </svg>
        </span>
    </span>
</button>
EOF;
