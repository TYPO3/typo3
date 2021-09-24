<?php

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
      'preCode' => '<span title="tt_content:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">
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
    1 =>
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>header_link, "typolink"</em> : <span title="file:2">file:2</span><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Record <strong>sys_file:2</strong>',
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
      'controls' => '<select name="tx_impexp[softrefCfg][2487ce518ed56d22f20f259928ff43f1][mode]"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select><br/>',
      'message' => '',
    ],
    2 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'rel',
      'msg' => 'LOST RELATION (Path: /)',
      'title' => '<span title="/">sys_file:2</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger" title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-exclamation-triangle" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '<span class="text-danger">LOST RELATION (Path: /)</span>',
    ],
    3 =>
    [
      'ref' => 'tt_content:2',
      'type' => 'record',
      'msg' => '',
      'preCode' => '<span title="tt_content:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text" /></svg>
	</span>
	
</span></span>',
      'title' => 'Test content 2',
      'active' => 'active',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][tt_content:2]" id="checkExcludett_content:2" value="1" />
            <label for="checkExcludett_content:2">Exclude</label>',
      'message' => '',
    ],
    4 =>
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>header_link, "typolink"</em> : <span title="file:4">file:4</span><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Record <strong>sys_file:4</strong>',
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
      'controls' => '<select name="tx_impexp[softrefCfg][81b8b33df54ef433f1cbc7c3e513e6c4][mode]"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select><br/>',
      'message' => '',
    ],
    5 =>
    [
      'ref' => 'sys_file:4',
      'type' => 'rel',
      'msg' => 'LOST RELATION (Path: /)',
      'title' => '<span title="/">sys_file:4</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger" title="sys_file:4"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-exclamation-triangle" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '<span class="text-danger">LOST RELATION (Path: /)</span>',
    ],
    6 =>
    [
      'ref' => 'tt_content:3',
      'type' => 'record',
      'msg' => '',
      'preCode' => '<span title="tt_content:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text" /></svg>
	</span>
	<span class="icon-overlay icon-overlay-hidden"><svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/overlay.svg#overlay-hidden" /></svg></span>
</span></span>',
      'title' => 'Test content 3',
      'active' => 'hidden',
      'controls' => '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][tt_content:3]" id="checkExcludett_content:3" value="1" />
            <label for="checkExcludett_content:3">Exclude</label>',
      'message' => '',
    ],
    7 =>
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>header_link, "typolink"</em> : <span title="file:3">file:3</span><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Record <strong>sys_file:3</strong>',
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
      'controls' => '<select name="tx_impexp[softrefCfg][0b1253ebf70ef5be862f29305e404edc][mode]"><option value="" selected="selected"></option><option value="editable">Editable</option><option value="exclude">Exclude</option></select><br/>',
      'message' => '',
    ],
    8 =>
    [
      'ref' => 'sys_file:3',
      'type' => 'rel',
      'msg' => 'LOST RELATION (Path: /)',
      'title' => '<span title="/">sys_file:3</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="text-danger" title="sys_file:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-dialog-warning" data-identifier="status-dialog-warning">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-exclamation-triangle" /></svg>
	</span>
	
</span></span>',
      'controls' => '',
      'message' => '<span class="text-danger">LOST RELATION (Path: /)</span>',
    ],
  ],
];
