<?php

return [
    'dependencies' => [],
    'imports' => [
        '@typo3/core/' => [
            'path' => 'EXT:core/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:core/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'autosize' => 'EXT:core/Resources/Public/JavaScript/Contrib/autosize.js',
        'bootstrap' => 'EXT:core/Resources/Public/JavaScript/Contrib/bootstrap.js',
        'cropperjs' => 'EXT:core/Resources/Public/JavaScript/Contrib/cropperjs.js',
        'css-tree' => 'EXT:core/Resources/Public/JavaScript/Contrib/css-tree.js',
        'dompurify' => 'EXT:core/Resources/Public/JavaScript/Contrib/dompurify.js',
        'flatpickr' => 'EXT:core/Resources/Public/JavaScript/Contrib/flatpickr.js',
        'flatpickr/' => 'EXT:core/Resources/Public/JavaScript/Contrib/flatpickr/',
        'flatpickr/dist/l10n' => 'EXT:core/Resources/Public/JavaScript/Contrib/flatpickr/dist/l10n.js',
        // legacy, has ben renamed 'flatpickr/dist/l10n'
        'flatpickr/locales' => 'EXT:core/Resources/Public/JavaScript/Contrib/flatpickr/dist/l10n.js',
        'interactjs' => 'EXT:core/Resources/Public/JavaScript/Contrib/interactjs.js',
        'jquery' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery.js',
        'jquery/' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery/',
        '@lit/reactive-element' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit/reactive-element/reactive-element.js',
        '@lit/reactive-element/' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit/reactive-element/',
        '@lit/task' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit/task/index.js',
        '@lit/task/' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit/task/',
        // @internal @lib-labs/motion shall not be used by extensions yet
        '@lit-labs/motion' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit-labs/motion/index.js',
        '@lit-labs/motion/' => 'EXT:core/Resources/Public/JavaScript/Contrib/@lit-labs/motion/',
        'lit' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit/index.js',
        'lit/' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit/',
        'lit-element' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit-element/index.js',
        'lit-element/' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit-element/',
        'lit-html' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit-html/lit-html.js',
        'lit-html/' => 'EXT:core/Resources/Public/JavaScript/Contrib/lit-html/',
        'luxon' => 'EXT:core/Resources/Public/JavaScript/Contrib/luxon.js',
        'nprogress' => 'EXT:core/Resources/Public/JavaScript/Contrib/nprogress.js',
        'marked' => 'EXT:core/Resources/Public/JavaScript/Contrib/marked.js',
        'shortcut-buttons-flatpickr' => 'EXT:core/Resources/Public/JavaScript/Contrib/shortcut-buttons-flatpickr.js',
        // legacy, has ben renamed 'shortcut-buttons-flatpickr'
        'flatpickr/plugins/shortcut-buttons.min.js' => 'EXT:core/Resources/Public/JavaScript/Contrib/shortcut-buttons-flatpickr.js',
        'sortablejs' => 'EXT:core/Resources/Public/JavaScript/Contrib/sortablejs.js',
        'tablesort' => 'EXT:core/Resources/Public/JavaScript/Contrib/tablesort.js',
        // legacy, bundled into `tablesort`, kept to minimize likelihood of breaking 3rd party extensions
        'tablesort.dotsep.js' => 'EXT:core/Resources/Public/JavaScript/Contrib/tablesort.js',
        // legacy, bundled into `tablesort`, kept to minimize likelihood of breaking 3rd party extensions
        'tablesort.number.js' => 'EXT:core/Resources/Public/JavaScript/Contrib/tablesort.js',
        'taboverride' => 'EXT:core/Resources/Public/JavaScript/Contrib/taboverride.js',
    ],
];
