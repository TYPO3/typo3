Post-CVS checkout instructions:

If you check out the module "TYPO3core" from SourceForce CVS you will have to make a few symlinks in the
checked out source before it will work for you. Follow these guidelines:


- Go to the module directory (default is "TYPO3core")
- Create symlink for tslib:
	ln -s typo3/sysext/cms/tslib
- Go to typo3/ folder:
	cd typo3/
- Create symlinks for t3lib/ and other things:
	ln -s ../t3lib
	ln -s ../t3lib/thumbs.php
	ln -s ../t3lib/gfx
- Finally, go to the t3lib/fonts/ dir:
	cd t3lib/fonts/
- Create two symlinks to fonts:
	ln -s vera.ttf verdana.ttf
	ln -s nimbus.ttf arial.ttf

OR

- Go to the module directory (default is "TYPO3core")
- Run the create-symlinks.sh shell script.


Thats all. This procedure is only needed when you check out the source for the first time ever.


ABOUT GLOBAL EXTENSIONS:
Notice that the "typo3/ext/" folder is NOT a part of the TYPO3 core CVS.
From version 3.7.0 of TYPO3 this directory is considered locally composed and maintained.
This means you can put a custom collection of extensions here which you will have to maintain independantly of TYPO3 core.
Some of the old global extensions have been moved to be system extensions for your convenience.
Notice that individual extensions might infact have their own CVS project somewhere, like on
SourceForge.net, project "TYPO3 Extension Development Platform" (typo3xdev).


IMPORTANT POST-CHECKLIST:
Follow this list IMMEDIATELY after updating sources from CVS (both core and extensions):
- Update database: In the Install Tool, click "COMPARE" for "Update required tables" in "Database Analysis" section. You might dump the static tables as well, but less likely to be important
- "Clear temp_CACHED" files from "typo3conf/" of your sites
- "Clear All Cache"
- Using PHP-accelerator or other PHP cache? If you fatal PHP-errors, always remove the cached files (eg. "/tmp/phpa_*"), restart Apache and try again.
(Hint: Take a look at "misc/superadmin.php" script which will greatly help you to maintain multiple TYPO3 installations when updating)


COMMITING CHANGES TO THE CORE:
This is only allowed for members of the core team (http://typo3.org/projects/teams-and-projects/#team_10) who is also having "developer" status on SourceForge (http://sourceforge.net/project/memberlist.php?group_id=20391)
There is defined a set of rules and conditions under which to commit to the core CVS.
These are found in "misc/core_cvs_rules.txt

- kasper