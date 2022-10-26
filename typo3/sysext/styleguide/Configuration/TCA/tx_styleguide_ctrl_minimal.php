<?php

return [
   'ctrl' => [
       'title' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitle',
       'label' => 'title',
       'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
       'security' => [
           'ignorePageTypeRestriction' => true,
       ],
   ],
   'columns' => [
       'title' => [
           'label' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:minimalTableTitleField',
           'config' => [
               'type' => 'input',
           ],
       ],
   ],
   'types' => [
      '0' => [
         'showitem' => 'title',
      ],
   ],
];
