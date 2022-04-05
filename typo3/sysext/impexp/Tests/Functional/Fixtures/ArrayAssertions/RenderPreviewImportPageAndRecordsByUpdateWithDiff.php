<?php

return [
  'update' => true,
  'showDiff' => true,
  'insidePageTree' =>
  [
    0 =>
    [
      'ref' => 'pages:0',
      'type' => 'record',
      'msg' => '',
      'preCode' => '<span title="pages:0"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => '',
      'active' => 'active',
      'updatePath' => '<strong>NEW!</strong>',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][pages:0]"><option value="0">Insert</option><option value="%s">Force UID [0] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => 'ERROR: One of the inputs were not an array!',
      'controls' => '',
      'message' => '',
    ],
    1 =>
    [
      'ref' => 'sys_file:1',
      'type' => 'record',
      'msg' => 'TABLE "sys_file" will be inserted on ROOT LEVEL! ',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-media-image" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'used-1.jpg',
      'active' => 'active',
      'updatePath' => '/',
      'updateMode' => '',
      'showDiffContent' => '<strong class="text-nowrap">[sys_file:1 =&gt; 1]:</strong>
<table class="table table-striped table-hover">
<tr><td>Identifier (identifier)</td><td><del>/user_upload/typo3_image3.</del><ins>/user_upload/used-1.</ins>jpg</td></tr>
<tr><td>Filename (name)</td><td><del>typo3_image3.</del><ins>used-1.</ins>jpg</td></tr>
<tr><td>SHA1 (sha1)</td><td><del>e873c1e2ffd0f191e183a1057de3eef4d62e782d</del><ins>da9acdf1e105784a57bbffec9520969578287797</ins></td></tr>
<tr><td>Size (size)</td><td><del>5565</del><ins>7958</ins></td></tr>
</table>',
      'controls' => '',
      'message' => '',
    ],
    2 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    3 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'record',
      'msg' => 'TABLE "sys_file" will be inserted on ROOT LEVEL! ',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-media-image" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'used-2.jpg',
      'active' => 'active',
      'updatePath' => '/',
      'updateMode' => '',
      'showDiffContent' => '<strong class="text-nowrap">[sys_file:2 =&gt; 1]:</strong>
<table class="table table-striped table-hover">
<tr><td>Identifier (identifier)</td><td><del>/user_upload/used-2.</del><ins>/user_upload/typo3_image3.</ins>jpg</td></tr>
<tr><td>Filename (name)</td><td><del>used-2.</del><ins>typo3_image3.</ins>jpg</td></tr>
<tr><td>SHA1 (sha1)</td><td><del>c3511df85d21bc578faf71c6a19eeb3ff44af370</del><ins>e873c1e2ffd0f191e183a1057de3eef4d62e782d</ins></td></tr>
<tr><td>Size (size)</td><td><del>7425</del><ins>5565</ins></td></tr>
</table>',
      'controls' => '',
      'message' => '',
    ],
    4 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    5 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'record',
      'msg' => 'TABLE "sys_file_storage" will be inserted on ROOT LEVEL! ',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-sys_file_storage" data-identifier="mimetypes-x-sys_file_storage">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-sys_file_storage" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'fileadmin',
      'active' => 'active',
      'updatePath' => '/',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][sys_file_storage:1]"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => '<strong class="text-nowrap">[sys_file_storage:1 =&gt; 1]:</strong>' . "\n"
          . '<table class="table table-striped table-hover">' . "\n"
          . '<tr><td>Driver Configuration (configuration)</td><td>' . "\n\n"
          . '<del>    \\n        \\n            \\n                \\n                    fileadmin/\\n                \\n                \\n                    relative\\n                \\n                \\n                    1\\n                \\n            \\n        \\n    \\n</del><ins>' . "\t\n"
          . "\t\t\n"
          . "\t\t\t\n"
          . "\t\t\t\t\n"
          . "\t\t\t\t\t" . 'fileadmin/' . "\n"
          . "\t\t\t\t\n"
          . "\t\t\t\t\n"
          . "\t\t\t\t\t" . 'relative' . "\n"
          . "\t\t\t\t\n"
          . "\t\t\t\t\n"
          . "\t\t\t\t\t" . '1' . "\n"
          . "\t\t\t\t\n"
          . "\t\t\t\n"
          . "\t\t\n"
          . "\t\n"
          . '</ins></td></tr>
<tr><td>Is default storage? (is_default)</td><td><del>Yes</del><ins>No</ins></td></tr>
<tr><td>Description (description)</td><td><strong>Field missing</strong> in database</td></tr>
</table>',
      'controls' => '',
      'message' => '',
    ],
    6 =>
    [
      'ref' => 'tt_content:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="tt_content:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text-picture" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'CE 1 first image',
      'active' => 'active',
      'updatePath' => '/Root/',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][tt_content:1]"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => '<strong class="text-nowrap">[tt_content:1 =&gt; 2]:</strong>
<table class="table table-striped table-hover">
<tr><td>Type (CType)</td><td><del>Text &amp; Images</del><ins>Text</ins></td></tr>
<tr><td>Header (header)</td><td><del>CE 1 first image</del><ins>Test content</ins></td></tr>
<tr><td>Images (image)</td><td>N/A</td></tr>
</table>',
      'controls' => '',
      'message' => '',
    ],
    7 =>
    [
      'ref' => 'sys_file_reference:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/Root/">used-1.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_reference:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    8 =>
    [
      'ref' => 'sys_file:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-1.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    9 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    10 =>
    [
      'ref' => 'tt_content:2',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="tt_content:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text-picture" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'CE 2 second image',
      'active' => 'active',
      'updatePath' => '/Root/',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][tt_content:2]"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => '<strong class="text-nowrap">[tt_content:2 =&gt; 1]:</strong>' . "\n"
          . '<table class="table table-striped table-hover">' . "\n"
          . '<tr><td>Type (CType)</td><td><del>Text &amp; Images</del><ins>Text</ins></td></tr>' . "\n"
          . '<tr><td>Header (header)</td><td><del>CE 2 second image</del><ins>Test content 2</ins></td></tr>' . "\n"
          . '<tr><td>Images (image)</td><td>N/A</td></tr>' . "\n"
          . '</table>',
      'controls' => '',
      'message' => '',
    ],
    11 =>
    [
      'ref' => 'sys_file_reference:2',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/Root/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_reference:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    12 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    13 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    14 =>
    [
      'ref' => 'tt_content:3',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="tt_content:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-x-content-text-picture" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'CE 3 second image',
      'active' => 'active',
      'updatePath' => '<strong>NEW!</strong>',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][tt_content:3]"><option value="0">Insert</option><option value="%s">Force UID [3] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => 'ERROR: One of the inputs were not an array!',
      'controls' => '',
      'message' => '',
    ],
    15 =>
    [
      'ref' => 'sys_file_reference:3',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/Root/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_reference:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    16 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    17 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    18 =>
    [
      'ref' => 'sys_file_reference:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file_reference:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-other-other" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'used-1.jpg',
      'active' => 'active',
      'updatePath' => '<strong>NEW!</strong>',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][sys_file_reference:1]"><option value="0">Insert</option><option value="%s">Force UID [1] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => 'ERROR: One of the inputs were not an array!',
      'controls' => '',
      'message' => '',
    ],
    19 =>
    [
      'ref' => 'sys_file:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-1.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    20 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    21 =>
    [
      'ref' => 'sys_file_reference:2',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file_reference:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-other-other" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'used-2.jpg',
      'active' => 'active',
      'updatePath' => '<strong>NEW!</strong>',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][sys_file_reference:2]"><option value="0">Insert</option><option value="%s">Force UID [2] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => 'ERROR: One of the inputs were not an array!',
      'controls' => '',
      'message' => '',
    ],
    22 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    23 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    24 =>
    [
      'ref' => 'sys_file_reference:3',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="sys_file_reference:3"><span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg#mimetypes-other-other" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => 'used-2.jpg',
      'active' => 'active',
      'updatePath' => '<strong>NEW!</strong>',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][sys_file_reference:3]"><option value="0">Insert</option><option value="%s">Force UID [3] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => 'ERROR: One of the inputs were not an array!',
      'controls' => '',
      'message' => '',
    ],
    25 =>
    [
      'ref' => 'sys_file:2',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">used-2.jpg</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file:2"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    26 =>
    [
      'ref' => 'sys_file_storage:1',
      'type' => 'rel',
      'msg' => '',
      'title' => '<span title="/">fileadmin</span>',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="" title="sys_file_storage:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-check" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'controls' => '',
      'message' => '',
    ],
    27 =>
    [
      'ref' => 'pages:1',
      'type' => 'record',
      'msg' => '',
      'preCode' => '&nbsp;&nbsp;&nbsp;&nbsp;<span title="pages:1"><span class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default">' . "\n"
          . "\t" . '<span class="icon-markup">' . "\n"
          . '<svg class="icon-color"><use xlink:href="typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg#apps-pagetree-page-default" /></svg>' . "\n"
          . "\t" . '</span>' . "\n"
          . "\t\n"
          . '</span></span>',
      'title' => '<a href="#" >Root</a>',
      'active' => 'active',
      'updatePath' => '/',
      'updateMode' => sprintf('<select name="tx_impexp[import_mode][pages:1]"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
      'showDiffContent' => '<strong class="text-nowrap">[pages:1 =&gt; 1]:</strong>' . "\n"
          . 'Match',
      'controls' => '',
      'message' => '',
    ],
  ],
  'outsidePageTree' =>
  [
  ],
];
