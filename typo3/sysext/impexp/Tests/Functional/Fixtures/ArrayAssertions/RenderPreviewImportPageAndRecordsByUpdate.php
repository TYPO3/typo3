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

$fileMtime = filemtime(__DIR__ . '/../../../../Resources/Public/Icons/status-reference-soft.png');
$fileMtimeApps = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/apps.svg');
$fileMtimeMime = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg');
$fileMtimeActions = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/actions.svg');
return [
    'update' => true,
    'showDiff' => false,
    'insidePageTree' =>
    [
        0 =>
        [
            'ref' => 'pages:1',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span title="pages:1" class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg?' . $fileMtimeApps . '#apps-pagetree-page-default" /></svg>
	</span>
	
</span>',
            'title' => 'Root',
            'active' => 'active',
            'updatePath' => '/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][pages:1]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'controls' => '',
            'message' => '',
        ],
        1 =>
        [
            'ref' => 'tt_content:1',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="tt_content:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text" /></svg>
	</span>
	
</span>',
            'title' => 'Test content',
            'active' => 'active',
            'updatePath' => '/Root/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][tt_content:1]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'controls' => '',
            'message' => '',
        ],
        2 =>
        [
            'ref' => 'SOFTREF',
            'type' => 'softref',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="SOFTREF" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft" aria-hidden="true">
	<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png?' . $fileMtime . '" width="16" height="16" alt="" />
	</span>
	
</span>',
            'title' => '<em>header_link, "typolink"</em>: <span title="file:2">file:2</span><br><span class="indent indent-inline-block" style="--indent-level: 2"></span> <strong>Record</strong> sys_file:2',
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
            'controls' => '',
            'message' => '',
        ],
        3 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => 'LOST RELATION (Path: /)',
            'title' => '<span title="/">sys_file:2</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-exclamation-triangle" /></svg>
	</span>
	
</span>',
            'controls' => '',
            'message' => '',
        ],
        4 =>
        [
            'ref' => 'tt_content:2',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="tt_content:2" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text" /></svg>
	</span>
	
</span>',
            'title' => 'Test content 2',
            'active' => 'active',
            'updatePath' => '/Root/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][tt_content:2]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'controls' => '',
            'message' => '',
        ],
        5 =>
        [
            'ref' => 'SOFTREF',
            'type' => 'softref',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="SOFTREF" class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft" aria-hidden="true">
	<span class="icon-markup">
<img src="/typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png?' . $fileMtime . '" width="16" height="16" alt="" />
	</span>
	
</span>',
            'title' => '<em>header_link, "typolink"</em>: <span title="file:4">file:4</span><br><span class="indent indent-inline-block" style="--indent-level: 2"></span> <strong>Record</strong> sys_file:4',
            '_softRefInfo' =>
            [
                'field' => 'header_link',
                'spKey' => 'typolink',
                'matchString' => 'file:4',
                'subst' =>
                [
                    'type' => 'db',
                    'recordRef' => 'sys_file:4',
                    'tokenID' => '81b8b33df54ef433f1cbc7c3e513e6c4',
                    'tokenValue' => 'file:4',
                ],
            ],
            'controls' => '',
            'message' => '',
        ],
        6 =>
        [
            'ref' => 'sys_file:4',
            'type' => 'rel',
            'msg' => 'LOST RELATION (Record not found!)',
            'title' => '<span title="/">sys_file:4</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file:4" class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-exclamation-triangle" /></svg>
	</span>
	
</span>',
            'controls' => '',
            'message' => '',
        ],
        7 =>
        [
            'ref' => 'pages:2',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="pages:2" class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default" aria-hidden="true">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg?' . $fileMtimeApps . '#apps-pagetree-page-default" /></svg>
	</span>
	
</span>',
            'title' => 'Dummy 1-2',
            'active' => 'active',
            'updatePath' => '/Root/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][pages:2]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'controls' => '',
            'message' => '',
        ],
    ],
    'outsidePageTree' =>
    [
    ],
];
