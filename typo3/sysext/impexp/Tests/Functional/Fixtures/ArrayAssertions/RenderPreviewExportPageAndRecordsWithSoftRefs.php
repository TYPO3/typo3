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

return [
  'update' => false,
  'showDiff' => false,
  'insidePageTree' =>
  [
    0 =>
    [
      'ref' => 'pages:0',
      'type' => 'record',
      'msg' => '',
      'preCode' => '<span title="pages:0"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>
	</span>
	
</span></span>',
      'title' => '',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][pages:0]" id="checkExcludepages:0" value="1" />
            <label for="checkExcludepages:0">Exclude</label>',
      'message' => '',
    ],
    1 =>
    [
      'ref' => 'be_users:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="be_users:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-user-admin" data-identifier="status-user-admin">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/status.svg#status-user-admin" /></svg>
	</span>
	
</span></span>',
      'title' => 'admin',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][be_users:1]" id="checkExcludebe_users:1" value="1" />
            <label for="checkExcludebe_users:1">Exclude</label>',
      'message' => '',
    ],
    2 =>
    [
      'ref' => 'sys_file:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-media-image" /></svg>
	</span>
	
</span></span>',
      'title' => 'typo3_image2.jpg',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][sys_file:1]" id="checkExcludesys_file:1" value="1" />
            <label for="checkExcludesys_file:1">Exclude</label>',
      'message' => '',
    ],
    3 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '',
    ],
    4 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-media-image" /></svg>
	</span>
	
</span></span>',
      'title' => 'typo3_image3.jpg',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][sys_file:2]" id="checkExcludesys_file:2" value="1" />
            <label for="checkExcludesys_file:2">Exclude</label>',
      'message' => '',
    ],
    5 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '',
    ],
    6 =>
    [
      'ref' => 'sys_file:3',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-media-image" /></svg>
	</span>
	
</span></span>',
      'title' => 'typo3_image5.jpg',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][sys_file:3]" id="checkExcludesys_file:3" value="1" />
            <label for="checkExcludesys_file:3">Exclude</label>',
      'message' => '',
    ],
    7 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '',
    ],
    8 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-sys_file_storage" data-identifier="mimetypes-x-sys_file_storage">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-sys_file_storage" /></svg>
	</span>
	
</span></span>',
      'title' => 'fileadmin',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][sys_file_storage:1]" id="checkExcludesys_file_storage:1" value="1" />
            <label for="checkExcludesys_file_storage:1">Exclude</label>',
      'message' => '',
    ],
    9 =>
    [
      'ref' => 'pages:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="pages:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>
	</span>
	
</span></span>',
      'title' => '<a href="#" >Root</a>',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][pages:1]" id="checkExcludepages:1" value="1" />
            <label for="checkExcludepages:1">Exclude</label>',
      'message' => '',
    ],
    10 =>
    [
      'ref' => 'tt_content:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="tt_content:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text" /></svg>
	</span>
	
</span></span>',
      'title' => 'Test content',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][tt_content:1]" id="checkExcludett_content:1" value="1" />
            <label for="checkExcludett_content:1">Exclude</label>',
      'message' => '',
    ],
    11 =>
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>header_link, "typolink"</em> : <span title="file:1">file:1</span><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Record <strong>sys_file:1</strong>',
      '_softRefInfo' =>
      [
        'field' => 'header_link',
        'spKey' => 'typolink',
        'matchString' => 'file:1',
        'subst' =>
        [
          'type' => 'db',
          'recordRef' => 'sys_file:1',
          'tokenID' => '2487ce518ed56d22f20f259928ff43f1',
          'tokenValue' => 'file:1',
        ],
      ],
      'controls' => '<select name="tx_impexp[softrefCfg][2487ce518ed56d22f20f259928ff43f1][mode]"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select><br/>',
      'message' => '',
    ],
    12 =>
    [
      'ref' => 'sys_file:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">typo3_image2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '',
    ],
    13 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '',
    ],
    14 =>
    [
      'ref' => 'pages:2',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="pages:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>
	</span>
	
</span></span>',
      'title' => '<a href="#" >Dummy 1-2</a>',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][pages:2]" id="checkExcludepages:2" value="1" />
            <label for="checkExcludepages:2">Exclude</label>',
      'message' => '',
    ],
    15 =>
    [
      'ref' => 'pages:3',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="pages:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>
	</span>
	<span class="icon-overlay icon-overlay-hidden"><svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/overlay.svg#overlay-hidden" /></svg></span>
</span></span>',
      'title' => '<a href="#" >Dummy 1-3</a>',
      'active' => 'hidden',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][pages:3]" id="checkExcludepages:3" value="1" />
            <label for="checkExcludepages:3">Exclude</label>',
      'message' => '',
    ],
  ],
  'outsidePageTree' =>
  [
  ],
];
