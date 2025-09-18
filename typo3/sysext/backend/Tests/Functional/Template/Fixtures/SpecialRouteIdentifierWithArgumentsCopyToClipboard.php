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
<div class="btn-group">
    <button
        type="button"
        class="btn btn-sm btn-default dropdown-toggle"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        title="Share">
        <span
            class="t3js-icon icon icon-size-small icon-state-default icon-actions-share-alt"
            data-identifier="actions-share-alt" aria-hidden="true">
            <span class="icon-markup">
                <svg class="icon-color">
                    <use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?{$fileMtimeActions}#actions-share-alt" />
                </svg>
            </span>
        </span>
    </button>
    <ul class="dropdown-menu">
        <li>
            <button
                data-dispatch-action="TYPO3.ShortcutMenu.createShortcut"
                data-dispatch-args="[&amp;quot;record_edit&amp;quot;,&amp;quot;{\u0022id\u0022:123,\u0022edit\u0022:{\u0022pages\u0022:{\u0022123\u0022:\u0022edit\u0022},\u0022overrideVals\u0022:{\u0022pages\u0022:{\u0022sys_language_uid\u0022:1}}}}&amp;quot;,&amp;quot;Edit record&amp;quot;,&amp;quot;Create a bookmark to this record&amp;quot;,&amp;quot;{\$target}&amp;quot;]"
                class="dropdown-item dropdown-item-spaced"
                title="Create a bookmark to this record">
                <span
                    class="t3js-icon icon icon-size-small icon-state-default icon-actions-system-shortcut-new"
                    data-identifier="actions-system-shortcut-new" aria-hidden="true">
                    <span class="icon-markup">
                        <svg class="icon-color">
                            <use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?{$fileMtimeActions}#actions-star" />
                        </svg>
                    </span>
                </span>Create a bookmark to this record
            </button>
        </li>
        <li>
            <typo3-copy-to-clipboard
                text="http://example.com/typo3/record/edit?id=123&amp;edit%5Bpages%5D%5B123%5D=edit&amp;edit%5BoverrideVals%5D%5Bpages%5D%5Bsys_language_uid%5D=1"
                class="dropdown-item dropdown-item-spaced"
                title="Copy URL of this record">
                <span
                    class="t3js-icon icon icon-size-small icon-state-default icon-actions-link"
                    data-identifier="actions-link" aria-hidden="true">
                    <span class="icon-markup">
                        <svg class="icon-color">
                            <use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?{$fileMtimeActions}#actions-link" />
                        </svg>
                    </span>
                </span>Copy URL of this record
            </typo3-copy-to-clipboard>
        </li>
    </ul>
</div>
EOF;
