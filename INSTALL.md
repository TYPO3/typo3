INSTALLING TYPO3
================

TYPO3 is an open source PHP based web content management system released
under the GNU GPL. TYPO3 is copyright (c) 1999-2015 by Kasper Skaarhoj.

This document describes:

* System requirements for TYPO3
* Installation routine
* Upgrade routine

Client browser support
----------------------

The TYPO3 backend is accessed through a web browser. TYPO3 CMS 7
supports the following web browsers:

* Internet Explorer 9 and later
* Google Chrome (Windows, MacOS, Linux)
* Firefox (Windows, MacOS, Linux)
* Safari on MacOS
* and other compatible modern browsers

Server system requirements
--------------------------

TYPO3 requires a web server with a PHP environment and a database. The minimum
system requirements for running TYPO3 CMS 7 are:

* Webserver capable of running PHP applications (Apache, Nginx, IIS or other)
* PHP 5.5 up to 7
* MySQL 5.5 up to 5.7 or compatible
* more than 200 MB of disk space

Note: If you use any other webserver than Apache, make sure you add the necessary configuration normally
provided in the various `.htaccess` files inside the TYPO3 core. This configuration is security relevant,
therefore only experienced server administrators should create such configuration.

### MySQL environment

TYPO3 works with MySQL in the above mentioned versions. It will also work on
compatible "drop-in" replacements like MariaDB or Percona.

### MySQL required privileges

The MySQL user needs a least the following privileges on the TYPO3 database:

* SELECT, INSERT, UPDATE, DELETE
* CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES

It is recommended to also grant the following privileges:

* CREATE VIEW, SHOW VIEW
* EXECUTE, CREATE ROUTINE, ALTER ROUTINE

### PHP environment

* memory_limit set to at least 64M
* max_execution_time set to at least 30s (240s recommended)
* register_globals disabled
* AllowOverride in the Apache configuration includes "Indexes" and "FileInfo"
  (see FAQ below)

### PHP required extensions

Your PHP needs to support the following extensions. Install will
check if these are available.

* These are usually part of the standard PHP package on most distributions:
  * filter
  * hash
  * openssl
  * pcre >= 8.30
  * session
  * soap
  * SPL
  * standard
  * xml
  * zip
  * zlib

* These might have to be installed separately:
  * gd
  * json
  * mysqli

### Recommended setup

There are plenty of possible setups for high performance TYPO3 installations
(i.e. using Varnish Cache, Nginx, PHP-FPM, etc). Consider this resource for
more ideas or suggestions:  http://wiki.typo3.org/Performance_tuning

This is a basic recommended setup for best performance and increased
functionality:

* Apache with mod_expires and mod_rewrite enabled

* MySQL 5.5 or newer

* GraphicsMagick or ImageMagick v6 or newer installed on the server

* PHP
  * version 5.5 or later
  * memory_limit set to at least 128M
  * max_execution_time set to at least 240s
  * max_input_vars set to at least 1500
  * always_populate_raw_post_data set to -1 (PHP version >= 5.6, <7.0)

* Additional PHP extensions:
  * PHP opcode cache, i.e.: apc, xcache, eaccelerator, Zend Optimizer, wincache (in case of an IIS installation)
  * apcu caching (with at least 100 MB of memory available)
  * curl
  * mbstring
  * FreeType 2 (usually included within the PHP distribution)
  * bcmath or gmp (needed if you'd like to use the openid system extension)
  * fileinfo (mandatory for proper file type detection)

* PHP access to /dev/urandom or /dev/random on Unix-like platforms for
  increased security. Make sure to add "/dev/random:/dev/urandom" to
  open_basedir settings if you use it. If these paths are unavailable, TYPO3
  will attempt to simulate random number generation. This is less secure,
  reduces performance and throws out warnings in the TYPO3 system log.

* TYPO3 works with PHP's IPv6 support, which is enabled by default.
  If you compile PHP on your own, be aware not to use option "--disable-ipv6",
  because this will break the IPv6 support and the according unit tests.

Installation
------------

### Important note for upgrades from TYPO3 CMS versions **below 6.2 LTS**

It is not possible to upgrade any version below 6.2 LTS to 7 directly,
since some upgrade wizards are not available anymore on 7.

It is highly recommended to upgrade to 6.2 LTS first and continue with
a second upgrade to 7.

### If SSH and symlinks are possible

If you have SSH access to your webserver and are able to create symlinks,
this is the recommended way of setting up TYPO3 so that it can easily
be upgraded later through the Install Tool:

* Uncompress the `typo3_src-7.6.x.tar.gz` file one level above the Document
  Root of your Web server:
```
/var/www/site/htdocs/ $ cd ..
/var/www/site/ $ tar xzf typo3_src-7.6.x.tar.gz
```

* Important: If you use GIT to fetch the sources, don't forget to run the following commands,
otherwise your installation won't work!
```
cd typo3_src
composer install --no-dev
cd ..
```

* Create the symlinks in your Document Root:
```
  cd htdocs
  ln -s ../typo3_src-7.6.x typo3_src
  ln -s typo3_src/index.php
  ln -s typo3_src/typo3
```

* In case you use Apache, copy the .htaccess to your Document Root:
```
  cp typo3_src/_.htaccess .htaccess
```

You end up with the follow structure of files:

```
  typo3_src-7.6.x/
  htdocs/typo3_src -> ../typo3_src-7.6.x/
  htdocs/typo3 -> typo3_src/typo3/
  htdocs/index.php -> typo3_src/index.php
  htdocs/.htaccess
```

This allows you to upgrade TYPO3 later by simply replacing the symlink
with a newer version, or by using the integrated "Core Updater" which can
be found in the Install Tool.

### Windows specifics

On Windows Vista and newer you can create symbolic links using the `mklink` tool:
```
  mklink /D C:\<dir>\example.com\typo3_src C:\<dir>\typo3_src-7.6.x
  mklink C:\<dir>\example.com\index.php C:\<dir>\typo3_src-7.6.x\index.php
```

Windows users might need to copy `index.php` from the source directory to the
web site root directory in case the Windows version does not support links
for files.

TYPO3 Core upgrades through the Install Tool is not supported under
Windows.

### No SSH and symlinks possible (not recommended)

In case you only have FTP or SFTP access to your hosting environment, you
can still install TYPO3, but you won't easily be able to upgrade your
installation once a new patch-level release is out.

Please note that this is not a recommended setup!

* Uncompress `typo3_src-7.6.x.zip` locally
* Upload all files and subdirectories directly in your Document Root
  (where files that are served by your webserver are located).
* In case your provider uses Apache, rename the file `_.htaccess` to `.htaccess`.

You end up with this files in your Document Root:

```
 .htaccess
 ChangeLog
 GPL.txt
 index.php
 INSTALL.md
 LICENSE.txt
 NEWS.txt
 README.md
 typo3/
```

Installation: further steps
---------------------------

Now access the web server using a web browser. You will be redirected to the
Install Tool which will walk you through the steps for setting up TYPO3 for
the first time.

It will check if your environment conforms to the minimum system requirements
and gives you some suggestions on what to change in case there are any
discrepancies.

The Install Tool will create the required directory structure for you
(typo3conf, uploads, fileadmin, typo3temp).

Former versions of TYPO3 required the download of a "Dummy Package"
(or "Blank Package"). This is no longer required since version 6.2!

TYPO3 Security
--------------

To ensure a secure installation, you have to make sure that you keep your
TYPO3 core and the extensions up to date.

* Subscribe to the announcement mailing list. This will inform you about new
  releases of the TYPO3 core and security bulletins of core and community
  extensions.
  http://lists.typo3.org/cgi-bin/mailman/listinfo/typo3-announce

* Use the scheduler task "Update Extension List (em)" to update the list of
  available extensions regularly. You should check regularly, if new versions
  of these extensions are available and apply these updates.

* Please refer to official TYPO3 Security Guide for further information
  about security-related topics of TYPO3 CMS and the resources compiled by
  the Security Team.
  https://docs.typo3.org/typo3cms/SecurityGuide/
  https://typo3.org/teams/security/resources/

Installation FAQ
----------------

### 1
Q:  Why do I get "500 Server error" when I navigate to my TYPO3 web site
    immediately after installation?

A:  If you are using Apache web server, check the Apache error log for specifics
    on the error. The cause might be some missing module, or some syntax error
    in your .htaccess file. The error log is usually located in /var/log/apache2
    or /var/log/httpd. Check with your hosting provider if you are in doubt
    where the logs are located.

### 2
Q:  I went through the setup process and created an admin user. Why can't I log
    in now?

A:  If you use MySQL 5.x or newer, try setting it to "compatible" mode. Open the
    TYPO3 Install Tool under http://example.com/typo3/install/ (where
    example.com is the web site domain), navigate to "All configuration".
    Find "setDBinit", and add this line to the top of the input field:
```
	SET SESSION sql_mode=''
```

### 3
Q:  Some modules or extensions make Apache crash on Windows. What is the cause?

A:  Fluid uses complex regular expressions which require a lot of stack space
    during the first processing. On Windows the default stack size for Apache
    is a lot smaller than on unix. You can increase the size to 8MB (default on
    unix) by adding to the httpd.conf:
```
	<IfModule mpm_winnt_module>
		ThreadStackSize 8388608
	</IfModule>
```
    Restart Apache after this change.
