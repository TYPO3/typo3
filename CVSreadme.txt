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


As the last operation you have to copy the global extensions into their position in the typo3/ folder!
The global extensions are not found there in the TYPO3core module because they are technically not a part
of the core although they are distributed along with it whenever you get hold of the tar files. So from
the most recent tar package of TYPO3 source you can find that directory and copy in here.
Notice that each global extension might infact have its own CVS project somewhere, like on
SourceForge.net, project "TYPO3 Extension Development Platform".

Thats all. This procedure is only needed when you check out the source for the first time ever.


- kasper