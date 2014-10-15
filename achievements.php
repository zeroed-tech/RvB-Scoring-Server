<?php
require('include/config.inc.php');

if (!(getAccessLevel() == 1 && (getTeamType() == 1 || getTeamType() == 3))) {
    header("location: index.php");
    exit();
}

require('header.php');
?>
<div id="backPane" onclick="hidePopup();"></div>

<?php


$achievements = getAchievementsForTeam();
foreach($achievements as $achievement){
    echo $achievement->printAchievement();
}


require('footer.php');
?>