<?php

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');


$urlP = parse_url(t3lib_div::getIndpEnv("REQUEST_URI"));
//header("Location: index.php?".$urlP["query"]);


$iFrame =0;	// I hoped that with an IFRAME the links to "_top" would not really open in "_top" but top of the IFRAME. But that was not the case...
if ($iFrame)	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Untitled</title>
</head>
<body marginwidth=0 marginheight=0 topmargin=0 leftmargin=0>
<?php
echo '<IFRAME src="index.php?'.$urlP["query"].'" id="VIEWFRAME" style="visibility: visible; position: absolute; left: 0px; top: 0px; height=100%; width=100%"></IFRAME>';
?>
</body>
</html>
<?php
} else {
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>View Frameset</title>
	<script language="javascript" type="text/javascript">
	if (top.fsMod) top.fsMod.recentIds['web'] = <?php echo intval(t3lib_div::_GET("id"));?>;
	</script>
</head>
<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
	<frame name="view_frame" src="index.php?<?php echo $urlP["query"];?>" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize>
	<frame name="dummy_frame" src="dummy.html" marginwidth="0" marginheight="0" frameborder="0" scrolling="0"  scrolling="no" noresize>
</frameset>
</html><?php
}
?>