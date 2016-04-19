<?php
return array(
    'ctrl' => array(
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'requestUpdate' => 'base',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden'
        ),
        'typeicon_classes' => array(
            'default' => 'mimetypes-x-sys_filemounts'
        ),
        'useColumnsForDefaultValues' => 'path,base',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title,path'
    ),
    'interface' => array(
        'showRecordFieldList' => 'title,hidden,path,base,description'
    ),
    'columns' => array(
        'title' => array(
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.title',
            'config' => array(
                'type' => 'input',
                'size' => '20',
                'max' => '30',
                'eval' => 'required,trim'
            )
        ),
        'hidden' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => array(
                'type' => 'check'
            )
        ),
        'description' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => array(
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => '2000',
            )
        ),
        'base' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.baseStorage',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file_storage',
                'allowNonIdValues' => true,
                'items' => array(
                    array('', 0)
                ),
                'size' => 1,
                'maxitems' => 1,
                'eval' => 'required',
                'range' => array(
                    'lower' => 1,
                )
            )
        ),
        'path' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.folder',
            'displayCond' => 'FIELD:base:>:0',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(),
                'itemsProcFunc' => 'TYPO3\\CMS\\Core\\Resource\\Service\\UserFileMountService->renderTceformsSelectDropdown',
            )
        ),
        'read_only' => array(
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.read_only',
            'config' => array(
                'type' => 'check'
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => '--palette--;;mount, description, base, path, read_only')
    ),
    'palettes' => array(
        'mount' => array(
            'showitem' => 'title,hidden',
        ),
    ),
);
