ABOUT GLOBAL EXTENSIONS:
Starting with version 4.0 of TYPO3 the directory typo3/ext/ is considered locally composed and maintained.
This means you can put a custom collection of extensions here which you will have to maintain independently of TYPO3core.
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
This is only allowed for members of the core team (http://typo3.org/teams/core/) who is also having "developer" status on SourceForge (http://sourceforge.net/project/memberlist.php?group_id=20391).
There is defined a set of rules and conditions under which to commit to the core CVS. These are found in "misc/core_cvs_rules.txt

- kasper
