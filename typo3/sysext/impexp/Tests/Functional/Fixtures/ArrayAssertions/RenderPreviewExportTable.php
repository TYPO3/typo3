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

$fileMtimeSoft = filemtime(__DIR__ . '/../../../../Resources/Public/Icons/status-reference-soft.png');
$fileMtimeMime = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg');
$fileMtimeActions = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/actions.svg');
$fileMtimeOverlay = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/overlay.svg');
return [
    'update' => false,
    'showDiff' => false,
    'insidePageTree' =>
    [
    ],
    'outsidePageTree' =>
    [
        0 =>
        [
            'ref' => 'tt_content:1',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span title="tt_content:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text" /></svg>
	</span>
	
</span>',
            'title' => 'Test content',
            'active' => 'active',
            'controls' => '<div class="form-check mb-0"><input class="form-check-input t3js-exclude-checkbox" type="checkbox" name="tx_impexp[exclude][tt_content:1]" id="checkExcludett_content:1" value="1" /><label class="form-check-label" for="checkExcludett_content:1">Exclude</label></div>',
            'message' => '',
        ],
        1 =>
        [
            'ref' => 'SOFTREF',
            'type' => 'softref',
            'msg' => '',
            'preCode' => '<span title="SOFTREF" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft" aria-hidden="true">
	<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png?' . $fileMtimeSoft . '" width="16" height="16" alt="" />
	</span>
	
</span>',
            'title' => '<em>header_link, "typolink"</em>: <span title="file:2">file:2</span><br><span class="indent indent-inline-block" style="--indent-level: 1"></span> <strong>Record</strong> sys_file:2',
            '_softRefInfo' =>
            [
                'field' => 'header_link',
                'spKey' => 'typolink',
                'matchString' => 'file:2',
                'subst' =>
                [
                    'type' => 'db',
                    'recordRef' => 'sys_file:2',
                    'tokenID' => '2487ce518ed56d22f20f259928ff43f1',
                    'tokenValue' => 'file:2',
                ],
            ],
            'controls' => '<select class="form-select form-select-sm" name="tx_impexp[softrefCfg][2487ce518ed56d22f20f259928ff43f1][mode]" style="width: 100px"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select>',
            'message' => '',
        ],
        2 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => 'LOST RELATION (Path: /)',
            'title' => '<span title="/">sys_file:2</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-exclamation-triangle" /></svg>
	</span>
	
</span>',
            'controls' => '',
            'message' => '<span class="text-danger">LOST RELATION (Path: /)</span>',
        ],
        3 =>
        [
            'ref' => 'tt_content:2',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span title="tt_content:2" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text" /></svg>
	</span>
	
</span>',
            'title' => 'Test content 2',
            'active' => 'active',
            'controls' => '<div class="form-check mb-0"><input class="form-check-input t3js-exclude-checkbox" type="checkbox" name="tx_impexp[exclude][tt_content:2]" id="checkExcludett_content:2" value="1" /><label class="form-check-label" for="checkExcludett_content:2">Exclude</label></div>',
            'message' => '',
        ],
        4 =>
        [
            'ref' => 'SOFTREF',
            'type' => 'softref',
            'msg' => '',
            'preCode' => '<span title="SOFTREF" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft" aria-hidden="true">
	<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png?' . $fileMtimeSoft . '" width="16" height="16" alt="" />
	</span>
	
</span>',
            'title' => '<em>header_link, "typolink"</em>: <span title="file:4">file:4</span>',
            '_softRefInfo' =>
            [
                'field' => 'header_link',
                'spKey' => 'typolink',
                'matchString' => 'file:4',
                'subst' =>
                [
                    'type' => 'external',
                    'tokenID' => '81b8b33df54ef433f1cbc7c3e513e6c4',
                    'tokenValue' => '4',
                ],
            ],
            'controls' => '<select class="form-select form-select-sm" name="tx_impexp[softrefCfg][81b8b33df54ef433f1cbc7c3e513e6c4][mode]" style="width: 100px"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select>',
            'message' => '',
        ],
        5 =>
        [
            'ref' => 'tt_content:3',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span title="tt_content:3" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text" /></svg>
	</span>
	<span class="icon-overlay icon-overlay-hidden"><svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/overlay.svg?' . $fileMtimeOverlay . '#overlay-hidden" /></svg></span>
</span>',
            'title' => 'Test content 3',
            'active' => 'hidden',
            'controls' => '<div class="form-check mb-0"><input class="form-check-input t3js-exclude-checkbox" type="checkbox" name="tx_impexp[exclude][tt_content:3]" id="checkExcludett_content:3" value="1" /><label class="form-check-label" for="checkExcludett_content:3">Exclude</label></div>',
            'message' => '',
        ],
        6 =>
        [
            'ref' => 'SOFTREF',
            'type' => 'softref',
            'msg' => '',
            'preCode' => '<span title="SOFTREF" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft" aria-hidden="true">
	<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png?' . $fileMtimeSoft . '" width="16" height="16" alt="" />
	</span>
	
</span>',
            'title' => '<em>header_link, "typolink"</em>: <span title="file:3">file:3</span><br><span class="indent indent-inline-block" style="--indent-level: 1"></span> <strong>Record</strong> sys_file:3',
            '_softRefInfo' =>
            [
                'field' => 'header_link',
                'spKey' => 'typolink',
                'matchString' => 'file:3',
                'subst' =>
                [
                    'type' => 'db',
                    'recordRef' => 'sys_file:3',
                    'tokenID' => '0b1253ebf70ef5be862f29305e404edc',
                    'tokenValue' => 'file:3',
                ],
            ],
            'controls' => '<select class="form-select form-select-sm" name="tx_impexp[softrefCfg][0b1253ebf70ef5be862f29305e404edc][mode]" style="width: 100px"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select>',
            'message' => '',
        ],
        7 =>
        [
            'ref' => 'sys_file:3',
            'type' => 'rel',
            'msg' => 'LOST RELATION (Path: /)',
            'title' => '<span title="/">sys_file:3</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file:3" class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-exclamation-triangle" /></svg>
	</span>
	
</span>',
            'controls' => '',
            'message' => '<span class="text-danger">LOST RELATION (Path: /)</span>',
        ],
    ],
];
