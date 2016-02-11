<?php
return [
    'ctrl' => [
        'title'    => 'Form engine - inline use combination mm',
        'label' => 'select_child',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],


    'columns' => [


        'select_parent' => [
            'label' => 'select parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombination',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'select_child' => [
            'label' => 'select child',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombination_child',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],


    ],


    'types' => [
        '1' => [
            'showitem' => 'select_parent, select_child',
        ],
    ],


];
