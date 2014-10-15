<?php
	require('include/config.inc.php');
	require('header.php');
	echo "<div id='motd'>".$CONFIG->getMOTD()."</div>";
?>
	<div id="backPane" onclick="hidePopup();"></div>
	<canvas id='clock' startDate="<?php echo str_replace('-', '/', $CONFIG->startDate); ?>" endDate="<?php echo str_replace('-', '/', $CONFIG->endDate); ?>"></canvas> <!-- Clock is generated in javascript then drawn on this element -->
<?php
	require('footer.php');
?>