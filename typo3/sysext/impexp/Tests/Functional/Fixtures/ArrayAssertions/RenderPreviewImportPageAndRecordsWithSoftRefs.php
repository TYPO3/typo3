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
    [
      'ref' => 'pages:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '<span title="pages:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>
	</span>
	
</span></span>',
      'title' => 'Congratulations',
      'active' => 'active',
      'controls' => '',
      'message' => '',
    ],
    [
      'ref' => 'tt_content:212',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="tt_content:212"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text">
	<span class="icon-markup">
<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text" /></svg>
	</span>
	
</span></span>',
      'title' => 'Professional Services',
      'active' => 'active',
      'controls' => '',
      'message' => '',
    ],
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>bodytext, "typolink_tag"</em> : <span title="&lt;a href=&quot;https://typo3.com/services/service-level-agreements/&quot; rel=&quot;noopener&quot; target=&quot;_blank&quot;&gt;">&lt;a href=&quot;https://typo3.com/services/service-level-agreements...</span>',
      '_softRefInfo' =>
      [
        'field' => 'bodytext',
        'spKey' => 'typolink_tag',
        'matchString' => '<a href="https://typo3.com/services/service-level-agreements/" rel="noopener" target="_blank">',
        'subst' =>
        [
          'type' => 'external',
          'tokenID' => '9700f40eaef01981e52bf05e7047c8db',
          'tokenValue' => 'https://typo3.com/services/service-level-agreements/',
        ],
      ],
      'controls' => '<br/><input type="text" name="tx_impexp[softrefInputValues][9700f40eaef01981e52bf05e7047c8db]" value="https://typo3.com/services/service-level-agreements/" />',
      'message' => '',
    ],
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>bodytext, "typolink_tag"</em> : <span title="&lt;a href=&quot;https://typo3.com/services/extended-support/&quot; rel=&quot;noopener&quot; target=&quot;_blank&quot;&gt;">&lt;a href=&quot;https://typo3.com/services/extended-support/&quot; rel=&quot;...</span>',
      '_softRefInfo' =>
      [
        'field' => 'bodytext',
        'spKey' => 'typolink_tag',
        'matchString' => '<a href="https://typo3.com/services/extended-support/" rel="noopener" target="_blank">',
        'subst' =>
        [
          'type' => 'external',
          'tokenID' => 'e0d903270af391bc1e7dddd38eae0072',
          'tokenValue' => 'https://typo3.com/services/extended-support/',
        ],
      ],
      'controls' => 'secondSoftRef<br/><input type="text" name="tx_impexp[softrefInputValues][e0d903270af391bc1e7dddd38eae0072]" value="https://typo3.com/services/extended-support/" />',
      'message' => '',
    ],
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>bodytext, "typolink_tag"</em> : <span title="&lt;a href=&quot;https://typo3.com/services/project-reviews/&quot; rel=&quot;noopener&quot; target=&quot;_blank&quot;&gt;">&lt;a href=&quot;https://typo3.com/services/project-reviews/&quot; rel=&quot;n...</span>',
      '_softRefInfo' =>
      [
        'field' => 'bodytext',
        'spKey' => 'typolink_tag',
        'matchString' => '<a href="https://typo3.com/services/project-reviews/" rel="noopener" target="_blank">',
        'subst' =>
        [
          'type' => 'external',
          'tokenID' => 'e56cdf0a5f2822c64c0111ad24373a54',
          'tokenValue' => 'https://typo3.com/services/project-reviews/',
        ],
      ],
      'controls' => '',
      'message' => '',
    ],
    [
      'ref' => 'SOFTREF',
      'type' => 'softref',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span title="SOFTREF"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-reference-soft" data-identifier="status-reference-soft">
	<span class="icon-markup">
<img src="typo3/sysext/impexp/Resources/Public/Icons/status-reference-soft.png" width="16" height="16" alt="" />
	</span>
	
</span></span>',
      'title' => '<em>bodytext, "typolink_tag"</em> : <span title="&lt;a href=&quot;https://typo3.com/products/integrations/google-ads-for-typo3&quot; rel=&quot;noopener&quot; target=&quot;_blank&quot;&gt;">&lt;a href=&quot;https://typo3.com/products/integrations/google-ads-...</span>',
      '_softRefInfo' =>
      [
        'field' => 'bodytext',
        'spKey' => 'typolink_tag',
        'matchString' => '<a href="https://typo3.com/products/integrations/google-ads-for-typo3" rel="noopener" target="_blank">',
        'subst' =>
        [
          'type' => 'external',
          'tokenID' => '98931a85208d5f3e47fb02c4ee38b94a',
          'tokenValue' => 'https://typo3.com/products/integrations/google-ads-for-typo3',
        ],
      ],
      'controls' => '',
      'message' => '',
    ],
  ],
  'outsidePageTree' =>
  [
  ],
];
