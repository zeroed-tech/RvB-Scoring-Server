<?php
	require('include/config.inc.php');
	require('header.php');
	echo "<div id='motd'><p>".str_replace("\n","<br />",$CONFIG->getMOTD())."</p></div>";
?>
	<div id="backPane" onclick="hidePopup();"></div>
	<canvas id='clock' startDate="<?php echo str_replace('-', '/', $CONFIG->startDate); ?>" duration="<?php echo str_replace('-', '/', $CONFIG->duration); ?>"></canvas> <!-- Clock is generated in javascript then drawn on this element -->
<?php
	require('footer.php');
?>