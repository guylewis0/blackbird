<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<!--
Copyright 2004-2008
Authors Charles Mastin & Joshua Rudd
c @ charlesmastin.com
contact @ joshuarudd.com


Portions of this software rely upon the following software which are covered by their respective license agreements
* Prototype.js
* Scriptaculous Library

-->

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>Blackbird</title>
		<?= $this->css() ?>
		<!-- library code -->
		<script type="text/javascript" src="<?= BASE ?>assets/js/prototype.js"></script>
		<script type="text/javascript" src="<?= BASE ?>assets/js/scriptaculous/scriptaculous.js?load=effects,dragdrop"></script>
		<script type="text/javascript" src="<?= BASE ?>assets/js/functions.js"></script>
		<script type="text/javascript" src="<?= BASE ?>assets/js/eventbroadcaster.js"></script>		
		<!-- app code -->
		<script type="text/javascript" src="<?= BASE ?>assets/js/blackbird.js"></script>
		<!-- widget code -->
		<script type="text/javascript" src="<?= BASE ?>assets/js/validator.js"></script>	
		<script type="text/javascript">
			document.observe('dom:loaded',function(){
				blackbird.setProperty("base","<?= BASE ?>");
			});
		</script>
	</head>

	<body id="body">
		
		<div id="bb_session_nav"></div>

		<div id="bb_main">
			<div class="bb_toolbar"></div>			
		</div>
		
		<div id="lightbox" style="display: nones;"><div class="wrapper"><div class="dialog"><?= $content['main'] ?></div></div></div>		
		
	</body>

</html>