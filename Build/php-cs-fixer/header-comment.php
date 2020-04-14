<?php

declare(strict_types=1);

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

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in([
        __DIR__ . '/../../typo3/sysext/*/Classes/',
        __DIR__ . '/../../typo3/sysext/*/Tests/',
        __DIR__ . '/../../typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial/Classes/',
        __DIR__ . '/../../typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler/Classes/',
        __DIR__ . '/../../typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_meta/Classes/',
        __DIR__ . '/../../typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example/Classes/',
        __DIR__ . '/../../typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/a/Classes/',
        __DIR__ . '/../../typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/b/Classes/',
        __DIR__ . '/../../typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test/Classes/',
    ])
    ->exclude('Unit/Core/Fixtures/test_extension/') // EXT:core
    ->exclude('Functional/Configuration/SiteConfiguration/Fixtures/Extensions/conf_overriding/') // EXT:core
    ->exclude('Functional/Fixtures/Extensions/irre_tutorial/') // EXT:core
    ->exclude('Functional/Fixtures/Extensions/test_datahandler/') // EXT:core
    ->exclude('Functional/Fixtures/Extensions/test_meta/') // EXT:core
    ->exclude('Functional/Fixtures/Extensions/test_resources/') // EXT:core
    ->exclude('Functional/Category/Collection/Fixtures/Extensions/test/') // EXT:core
    ->exclude('Functional/Database/Fixtures/Extensions/test_expressionbuilder/') // EXT:core
    ->exclude('Unit/Http/Fixtures/Package1') // EXT:core
    ->exclude('Unit/Http/Fixtures/Package2') // EXT:core
    ->exclude('Unit/Http/Fixtures/Package2Disables1') // EXT:core
    ->exclude('Unit/Http/Fixtures/Package2Replaces1') // EXT:core
    ->exclude('Functional/Fixtures/Extensions/blog_example/') // EXT:extbase
    ->exclude('Functional/Fixtures/Extensions/class_overriding/a/') // EXT:extbase
    ->exclude('Functional/Fixtures/Extensions/class_overriding/b/') // EXT:extbase
    ->exclude('Functional/Fixtures/Extensions/template_extension/') // EXT:impexp
    ->exclude('Functional/Hooks/Fixtures/test_resources/') // EXT:form
    ->exclude('Functional/Fixtures/Extensions/fluid_test/') // EXT:fluid
    ->exclude('Unit/Service/Fixtures/') // EXT:form
    ->exclude('Acceptance/Support/_generated') // EXT:core
    ->notName('Rfc822AddressesParser.php')
    ->notName('AdditionalConfiguration.php')
    ->notName('ext_localconf.php')
    ->notName('ext_tables.php')
    ->notName('ext_emconf.php')
    ->notName('install.php')
    ->notName('index.php')
;

$headerComment = <<<COMMENT
This file is part of the TYPO3 CMS project.

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
COMMENT;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(false)
    ->setRules([
        'header_comment' => [
            'header' => $headerComment,
            'comment_type' => 'comment',
            'separate' => 'both',
            'location' => 'after_declare_strict'
        ],
    ])
    ->setFinder($finder);
