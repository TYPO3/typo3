![TYPO3](http://typo3.org/typo3conf/ext/t3org_template/i/typo3-logo.png) <img align="right" width="128" height="128" src="https://cdn.rawgit.com/7elix/TYPO3.CMS.Styleguide/master/Resources/Public/Icons/module.svg">

TYPO3 CMS Backend Styleguide
============================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/7elix/TYPO3.CMS.Styleguide/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/7elix/TYPO3.CMS.Styleguide/?branch=master)

[![Code Coverage](https://scrutinizer-ci.com/g/7elix/TYPO3.CMS.Styleguide/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/7elix/TYPO3.CMS.Styleguide/?branch=master)

[![Build Status](https://travis-ci.org/7elix/TYPO3.CMS.Styleguide.svg)](https://travis-ci.org/7elix/TYPO3.CMS.Styleguide)

Welcome to the living Styleguide for TYPO3 CMS backend.
Presents supported styles for TYPO3 backend modules.

![](Documentation/Index.png)

[Official repository for TYPO3 CMS extension "styleguide" with changelog.](http://typo3.org/extensions/repository/view/styleguide)

# Table of content

1. Typography
2. TCA / Records
3. Trees
4. Tab
5. Tables
6. Buttons
7. Infobox
8. FlashMessages / Notification
9. Icons
10. Debug
11. Helpers

# Installation
This Styleguide comes as a TYPO3 extension for the TYPO3 backend. It appears as backend module within the Help/Manuals navigation section.

## Composer
With composer based [TYPO3 installation](https://wiki.typo3.org/Composer) add this Styleguide by running the following command on shell within project root (where root composer.json file resides):

```
composer require 7elix/styleguide
```

Composer will automatically find, download and extract the appropriate version into extension manager. Activate Styleguide extension from TYPO3 backend in Extension Manager.

Hint: If [helhum/typo3-console](https://github.com/helhum/typo3_console/) has been installed locally. Activate Styleguide extension on shell:

```
./typo3cms extension:install styleguide
```

## TYPO3 Extension Repository
Head to TYPO3 backend > Extension Manager > Get Extensions. Search for and install extension key „styleguide“. Activate Styleguide extension within TYPO3 backend in Extension Manager.

# Legal
Disclaimer: This styleguide does not look perfect - besides documentation the guide should also point out missing concepts and styles.
Therefore every imperfect style also is a todo. The solution could be included in the TYPO3 CMS core at any stage.

Questions? - Maintained by Felix Kopp and contributors.
Contact [@7elix](https://twitter.com/7elix).

This guide is highly inspired by Bootstrap, Zurb Foundation and Pattern Lab.
