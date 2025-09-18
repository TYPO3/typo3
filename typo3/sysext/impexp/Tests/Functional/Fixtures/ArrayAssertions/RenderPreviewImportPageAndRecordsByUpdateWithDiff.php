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

$fileMtimeApps = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/apps.svg');
$fileMtimeMime = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg');
$fileMtimeActions = filemtime(__DIR__ . '/../../../../../core/Resources/Public/Icons/T3Icons/sprites/actions.svg');
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
            'preCode' => '<span title="pages:0" class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg?' . $fileMtimeApps . '#apps-pagetree-page-default" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => '',
            'active' => 'active',
            'updatePath' => '<strong>NEW!</strong>',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][pages:0]" style="width: 100px"><option value="0">Insert</option><option value="%s">Force UID [0] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '',
            'controls' => '',
            'message' => '',
        ],
        1 =>
        [
            'ref' => 'sys_file:1',
            'type' => 'record',
            'msg' => 'TABLE "sys_file" will be inserted on ROOT LEVEL! ',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-media-image" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
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
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        3 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'record',
            'msg' => 'TABLE "sys_file" will be inserted on ROOT LEVEL! ',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-media-image" data-identifier="mimetypes-media-image" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-media-image" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
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
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        5 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'record',
            'msg' => 'TABLE "sys_file_storage" will be inserted on ROOT LEVEL! ',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-sys_file_storage" data-identifier="mimetypes-x-sys_file_storage" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-sys_file_storage" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'fileadmin',
            'active' => 'active',
            'updatePath' => '/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][sys_file_storage:1]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '<strong class="text-nowrap">[sys_file_storage:1 =&gt; 1]:</strong>' . "\n"
                . '<table class="table table-striped table-hover">' . "\n"
                . '<tr><td>Is default storage? (is_default)</td><td><del>Yes</del><ins>No</ins></td></tr>' . "\n"
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
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="tt_content:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text-picture" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'CE 1 first image',
            'active' => 'active',
            'updatePath' => '/Root/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][tt_content:1]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '<strong class="text-nowrap">[tt_content:1 =&gt; 2]:</strong>
<table class="table table-striped table-hover">
<tr><td>Type (CType)</td><td><ins>Regular </ins>Text <del>&amp; Images</del><ins>Element</ins></td></tr>
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
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file_reference:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        8 =>
        [
            'ref' => 'sys_file:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-1.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        9 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 4"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        10 =>
        [
            'ref' => 'tt_content:2',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="tt_content:2" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text-picture" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'CE 2 second image',
            'active' => 'active',
            'updatePath' => '/Root/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][tt_content:2]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '<strong class="text-nowrap">[tt_content:2 =&gt; 1]:</strong>' . "\n"
                . '<table class="table table-striped table-hover">' . "\n"
                . '<tr><td>Type (CType)</td><td><ins>Regular </ins>Text <del>&amp; Images</del><ins>Element</ins></td></tr>' . "\n"
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
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file_reference:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        12 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-2.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        13 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 4"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        14 =>
        [
            'ref' => 'tt_content:3',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="tt_content:3" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text-picture" data-identifier="mimetypes-x-content-text-picture" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-x-content-text-picture" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'CE 3 second image',
            'active' => 'active',
            'updatePath' => '<strong>NEW!</strong>',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][tt_content:3]" style="width: 100px"><option value="0">Insert</option><option value="%s">Force UID [3] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '',
            'controls' => '',
            'message' => '',
        ],
        15 =>
        [
            'ref' => 'sys_file_reference:3',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/Root/">used-2.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file_reference:3" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        16 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-2.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        17 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 4"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        18 =>
        [
            'ref' => 'sys_file_reference:1',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file_reference:1" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-other-other" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'used-1.jpg',
            'active' => 'active',
            'updatePath' => '<strong>NEW!</strong>',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][sys_file_reference:1]" style="width: 100px"><option value="0">Insert</option><option value="%s">Force UID [1] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '',
            'controls' => '',
            'message' => '',
        ],
        19 =>
        [
            'ref' => 'sys_file:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-1.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        20 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        21 =>
        [
            'ref' => 'sys_file_reference:2',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file_reference:2" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-other-other" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'used-2.jpg',
            'active' => 'active',
            'updatePath' => '<strong>NEW!</strong>',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][sys_file_reference:2]" style="width: 100px"><option value="0">Insert</option><option value="%s">Force UID [2] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '',
            'controls' => '',
            'message' => '',
        ],
        22 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-2.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        23 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        24 =>
        [
            'ref' => 'sys_file_reference:3',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="sys_file_reference:3" class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-other-other" data-identifier="mimetypes-other-other" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/mimetypes.svg?' . $fileMtimeMime . '#mimetypes-other-other" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'used-2.jpg',
            'active' => 'active',
            'updatePath' => '<strong>NEW!</strong>',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][sys_file_reference:3]" style="width: 100px"><option value="0">Insert</option><option value="%s">Force UID [3] (Admin)</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_FORCE_UID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
            'showDiffContent' => '',
            'controls' => '',
            'message' => '',
        ],
        25 =>
        [
            'ref' => 'sys_file:2',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">used-2.jpg</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 2"></span><span title="sys_file:2" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        26 =>
        [
            'ref' => 'sys_file_storage:1',
            'type' => 'rel',
            'msg' => '',
            'title' => '<span title="/">fileadmin</span>',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 3"></span><span title="sys_file_storage:1" class="t3js-icon icon icon-size-small icon-state-default icon-status-status-checked" data-identifier="status-status-checked" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg?' . $fileMtimeActions . '#actions-check" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'controls' => '',
            'message' => '',
        ],
        27 =>
        [
            'ref' => 'pages:1',
            'type' => 'record',
            'msg' => '',
            'preCode' => '<span class="indent indent-inline-block" style="--indent-level: 1"></span><span title="pages:1" class="t3js-icon icon icon-size-small icon-state-default icon-apps-pagetree-page-default" data-identifier="apps-pagetree-page-default" aria-hidden="true">' . "\n"
                . "\t" . '<span class="icon-markup">' . "\n"
                . '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/apps.svg?' . $fileMtimeApps . '#apps-pagetree-page-default" /></svg>' . "\n"
                . "\t" . '</span>' . "\n"
                . "\t\n"
                . '</span>',
            'title' => 'Root',
            'active' => 'active',
            'updatePath' => '/',
            'updateMode' => sprintf('<select class="form-select form-select-sm" name="tx_impexp[import_mode][pages:1]" style="width: 100px"><option value="0">Update</option><option value="%s">Import as new</option><option value="%s">Ignore PID</option><option value="%s">Exclude</option></select>', \TYPO3\CMS\Impexp\Import::IMPORT_MODE_AS_NEW, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_IGNORE_PID, \TYPO3\CMS\Impexp\Import::IMPORT_MODE_EXCLUDE),
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
