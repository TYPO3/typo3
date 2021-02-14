<?php

return [
   'ctrl' => [
       'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitle',
       'label' => 'title_field',
       'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
   ],
   'columns' => [
       'title_field' => [
           'label' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitleField',
           'config' => [
               'type' => 'input'
           ],
       ],
   ],
   'types' => [
      '0' => [
         'showitem' => 'title_field',
      ],
   ],
];
