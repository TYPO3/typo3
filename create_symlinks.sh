#!/bin/sh

# This procedure is only needed when you check out the source for the first time ever.


# Create symlink for tslib:
ln -s typo3/sysext/cms/tslib

# Go to typo3/ folder:
cd typo3/

# Create symlinks for t3lib/ and other things:
ln -s ../t3lib
ln -s ../t3lib/thumbs.php
ln -s ../t3lib/gfx

# Finally, go to the t3lib/fonts/ dir:
cd t3lib/fonts/

 #Create two symlinks to fonts:
ln -s vera.ttf verdana.ttf
ln -s nimbus.ttf arial.ttf

