<?php

// News: I had once said that when PHP4.0.5 comes out I will reccomend the built in
// ob_gzhandler over my code unless you are generating flash or images on the fly.
//
// I was wrong. PHP4.0.5 is out and ob_gzhandler doesn't work for me.

// Note: This is rather cool: http://Leknor.com/code/gziped.php
// It will calculate the effects of this class on a page.
// compression level, cpu time, download time, etc

// Note: this may better for some sites:
// http://www.remotecommunications.com/apache/mod_gzip/
// I've read that the above doesn't work with php output.

class gzip_encode {
    /*
     * gzip_encode - a class to gzip encode php output
     *
     * By Sandy McArthur, Jr. <Leknor@Leknor.com>
     *
     * Copyright 2001 (c) All Rights Reserved, All Responsibility Yours.
     *
     * This code is released under the GNU LGPL Go read it over here:
     * http://www.gnu.org/copyleft/lesser.html
     *
     * I do make one optional request, I would like an account on or a
     * copy of where this code is used. If that is not possible then
     * an email would be cool.
     *
     * How to use:
     * 1. Output buffering has to be turned on. You can do this with ob_start()
     *    <http://php.net/manual/function.ob-start.php> or in the php config
     *    file. Nothing bad happens if output buffering isn't turned on, your
     *    page just won't get compressed.
     * 2. Include the class file.
     * 3. At the _very_ end of your script create an instance of the encode
     *    class.
     *
     * eg:
     *	------------Start of file----------
     *	|<?php
     *	| ob_start();
     *	| include('class.gzip_encode.php');
     *	|?>
     *	|<HTML>
     *	|... the page ...
     *	|</HTML>
     *	|<?php
     *	| new gzip_encode();
     *	|?>
     *	-------------End of file-----------
     *
     * Things to note:
     * 1. There is no space before the beginning of the file and the '<?php ' tag
     * 2. The ob_start() line is optional if output buffering is turned on in
     *    the main config file.
     * 3. Turning on and off output buffering just won't work.
     * 4. There must be nothing after the last '?>' tag at the end of the file.
     *    Be careful of a space hiding there.
     * 5. There are better ways to compress served content but I think this is
     *    the only way to compress php output.
     * 6. Your auto_prepend_file is a good place for the ob_start() and
     *    your auto_append_file is a good place for new gzip_encode().
     * 7. If you put new gzip_encode() in your auto.append file then you can
     *    call ob_end_flush() in your script to disable compression.
     *
     * This was written from scratch from info freely available on the web.
     *
     * These site(s) were useful to me:
     *	http://www.php.net/manual/
     *	http://www.ietf.org/rfc/rfc2616.txt (Sections: 3.5, 14.3, 14.11)
     *
     * Requirments:
     *	PHP 4.0.1+:	I use the '===' operator, and output buffering, crc32();
     *	zlib:		Needed for the gzip encoding. (Odds are you have it)
     *
     * Benchmarks:
     *	Take a look at http://Leknor.com/code/gziped.php and feed it a page to
     *	get an idea of how it will preform on your data or page.
     *
     * To Do:
     * 1. I have reports of no content errors. I can't seem to duplicate this.
     *    Please visit my discussion boards if you think you may be able to help
     * 2. The Accept-Encoding isn't handled to spec. Check out 14.3 in RFC 2616
     *    to see how it should be done.
     *
     * Change Log:
     *	0.66:	Big bug fix. It wouldn't compress when it should.
     *	0.65:	Fix for PHP-4.0.5 suddenly removing the connection_timeout() function.
     *	0.62:	Fixed a typo
     *	0.61:	Detect file types more like described in the magic number files, also
     *		added detection for gzip and pk zip files.
     *	0.6:	Detect common file types that shouldn't be compressed, mainly
     *		for images and swf (Shockwave Flash doesn't really accept gzip)
     *	0.53:	Made gzip_accepted() method so everyone can detect if a page
     *		will be gzip'ed with ease.
     *	0.52:	Detection and graceful handling of improper install/missing libs
     *	0.51:	Added FreeBSD load average detection.
     *	0.5:	Passing true as the first parameter will try to calculate the
     *		compression level from the server's load average. Passing true
     *		as the second parameter will turn on debugging.
     *	0.4:	No longer uses a temp file to compress the output. Should speed
     *		thing up a bit and reduce wear on your hard disk. Also test if
     *		the http headers have been sent.
     *	0.31:	Made a small change to the tempnam() line to hopefully be more
     *		portable.
     *	0.3:	Added code for the 'x-gzip'. This is untested, I don't know of
     *		any browser that uses it but the RFC said to look out for it.
     *	0.2:	Checks for 'gzip' in the Accept-Encoding header
     *	0.1:	First working version.
     *
     * Thanks To (Suggestions and stuff):
     *	?@boas.anthro.mnsu.edu	http://php.net/manual/function.gzcompress.php
     *	Kaoslord		<kaoslord@chaos-productions.com>
     *	Michael R. Gile		<gilem@wsg.net>
     *	Christian Hamm		<chh@admaster.de>
     *
     * The most recent version is available at:
     *	http://Leknor.com/code/
     *
     */

    var $_version = 0.66; // Version of the gzip_encode class

    var $level;		// Compression level
    var $encoding;	// Encoding type
    var $crc;		// crc of the output
    var $size;		// size of the uncompressed content
    var $gzsize;	// size of the compressed content

    /*
     * gzip_encode constructor - gzip encodes the current output buffer
     * if the browser supports it.
     *
     * Note: all arguments are optionial.
     *
     * You can specify one of the following for the first argument:
     *	0:	No compression
     *	1:	Min compression
     *	...	Some compression (integer from 1 to 9)
     *	9:	Max compression
     *	true:	Determin the compression level from the system load. The
     *		higher the load the less the compression.
     *
     * You can specify one of the following for the second argument:
     *	true:	Don't actully output the compressed form but run as if it
     *		had. Used for debugging.
     */
    function gzip_encode($level = 3, $debug = false, $outputCompressedSizes=0) {
	if (!function_exists('gzcompress')) {
	    trigger_error('gzcompress not found, ' .
		    'zlib needs to be installed for gzip_encode',
		    E_USER_WARNING);
	    return;
	}
	if (!function_exists('crc32')) {
	    trigger_error('crc32() not found, ' .
		    'PHP >= 4.0.1 needed for gzip_encode', E_USER_WARNING);
	    return;
	}
	if (headers_sent()) return;
	if (connection_status() !== 0) return;
	$encoding = $this->gzip_accepted();
	if (!$encoding) return;
	$this->encoding = $encoding;

	if ($level === true) {
	    $level = $this->get_complevel();
	}
	$this->level = $level;

	$contents = ob_get_contents();
	if ($contents === false) return;

	$gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00"; // gzip header

		// By Kasper Skaarhoj, start
	if ($outputCompressedSizes)	{
		$contents.=chr(10)."<!-- Compressed, level ".$level.", original size was ".strlen($contents)." bytes. New size is ".strlen(gzcompress($contents, $level))." bytes -->";
		$size = strlen($contents);	// Must set again!
	}
		// By Kasper Skaarhoj, end

	$size = strlen($contents);
	$crc = crc32($contents);
	$gzdata .= gzcompress($contents, $level);
	$gzdata = substr($gzdata, 0, strlen($gzdata) - 4); // fix crc bug
	$gzdata .= pack("V",$crc) . pack("V", $size);

	$this->size = $size;
	$this->crc = $crc;
	$this->gzsize = strlen($gzdata);

	if ($debug) {
	    return;
	}

	ob_end_clean();
	Header('Content-Encoding: ' . $encoding);
	Header('Content-Length: ' . strlen($gzdata));
	Header('X-Content-Encoded-By: class.gzip_encode '.$this->_version);

	echo $gzdata;
    }


    /*
     * gzip_accepted() - Test headers for Accept-Encoding: gzip
     *
     * Returns: if proper headers aren't found: false
     *          if proper headers are found: 'gzip' or 'x-gzip'
     *
     * Tip: using this function you can test if the class will gzip the output
     *  without actually compressing it yet, eg:
     *    if (gzip_encode::gzip_accepted()) {
     *       echo "Page will be gziped";
     *    }
     *  note the double colon syntax, I don't know where it is documented but
     *  somehow it got in my brain.
     */
    function gzip_accepted() {
	if (strpos(getenv("HTTP_ACCEPT_ENCODING"), 'gzip') === false) return false;
	if (strpos(getenv("HTTP_ACCEPT_ENCODING"), 'x-gzip') === false) {
	    $encoding = 'gzip';
	} else {
	    $encoding = 'x-gzip';
	}

	// Test file type. I wish I could get HTTP response headers.
	$magic = substr(ob_get_contents(),0,4);
	if (substr($magic,0,2) === '^_') {
	    // gzip data
	    $encoding = false;
	} else if (substr($magic,0,3) === 'GIF') {
	    // gif images
	    $encoding = false;
	} else if (substr($magic,0,2) === "\xFF\xD8") {
	    // jpeg images
	    $encoding = false;
	} else if (substr($magic,0,4) === "\x89PNG") {
	    // png images
	    $encoding = false;
	} else if (substr($magic,0,3) === 'FWS') {
	    // Don't gzip Shockwave Flash files. Flash on windows incorrectly
	    // claims it accepts gzip'd content.
	    $encoding = false;
	} else if (substr($magic,0,2) === 'PK') {
	    // pk zip file
	    $encoding = false;
	}

	return $encoding;
    }

    /*
     * get_complevel() - The level of compression we should use.
     *
     * Returns an int between 0 and 9 inclusive.
     *
     * Tip: $gzleve = gzip_encode::get_complevel(); to get the compression level
     *      that will be used with out actually compressing the output.
     *
     * Help: if you use an OS other then linux please send me code to make
     * this work with your OS - Thanks
     */
    function get_complevel() {
	$uname = posix_uname();
	switch ($uname['sysname']) {
	    case 'Linux':
		$cl = (1 - $this->linux_loadavg()) * 10;
		$level = (int)max(min(9, $cl), 0);
		break;
	    case 'FreeBSD':
		$cl = (1 - $this->freebsd_loadavg()) * 10;
		$level = (int)max(min(9, $cl), 0);
		break;
	    default:
		$level = 3;
		break;
	}
	return $level;
    }

    /*
     * linux_loadavg() - Gets the max() system load average from /proc/loadavg
     *
     * The max() Load Average will be returned
     */
    function linux_loadavg() {
	$buffer = "0 0 0";
	$f = fopen("/proc/loadavg","rb");
	if (!feof($f)) {
	    $buffer = fgets($f, 1024);
	}
	fclose($f);
	$load = explode(" ",$buffer);
	return max((float)$load[0], (float)$load[1], (float)$load[2]);
    }

    /*
     * freebsd_loadavg() - Gets the max() system load average from uname(1)
     *
     * The max() Load Average will be returned
     *
     * I've been told the code below will work on solaris too, anyone wanna
     * test it?
     */
    function freebsd_loadavg() {
	$buffer= `uptime`;
	ereg("averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)", $buffer, $load);

	return max((float)$load[2], (float)$load[3], (float)$load[4]);
    }
}

?>