Version history:

3.2.3
-----
- The external method names are now loadCaptions() and addCaptions()

3.2.2
-----
- Added support for MP4 embedded captions, issue #122. Demo: http://ec2-75-101-198-99.compute-1.amazonaws.com:8080/plugins/flash/captions.html

3.2.1
-----
- Added ability to have line breaks with timed text caption files:
<p begin = "00:00:00.01" dur="04.00">
    A lazy fox jumps<br/>over a busy cat
</p>

3.2.0
-----
- Fixed visibility issue
- Fixed multiple lines subtitles (#36)
- Increasing font size when going fullscreen (#37)
- Clip's autoPlay field wasn't taken in account (#66)
- Wrong resize when going fullscreen if caption view was not displayed

3.1.4
-----
- Timed Text parsing was fixed

3.1.3
-----
Fixes:
- loadCaptions() now removes all previous captions before adding the loaded ones

3.1.2
------
- Now the captions can be initially made invisible by just specifying display: 'none' in the content plugin that is used
  to show the captions

3.1.1
-----
- added a file extension parameter to the loadCaptions external method

3.1.0
-----
- added a button to toggle the captions, new config option 'button' can be used to control it
- fixed error appearing in Firebug console with Timed Text captions: http://flowplayer.org/forum/8/16030)


3.0.0
-----
- the first release
