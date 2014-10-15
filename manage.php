<?php
require('include/config.inc.php');

if (!(getAccessLevel() == 1 && (getTeamType() == 2 || getTeamType() == 3))) {
    header("location: index.php");
    exit();
}

if(isset($_POST['data'])){
    $gameState = $CONFIG->getGameState();
    if($gameState == Config::RUNNING) {
        if (getAccessLevel() != 1) {
            echo json_encode(array('result' => 'false', 'message' => 'You must be logged in to perform this action'));
            exit();
        }
        require('challengeManagement.php');

        $data = json_decode($_POST['data']);

        switch ($data->functionCall) {
            case 'restart': {
                $challenge = getChallengeWithId($data->challengeId);
                $challengeName = createSanitisedChallengeName($challenge['name'], $_SESSION['teamid']);
                stopContainer($challengeName);
                startContainer($challengeName);
                echo json_encode(array('result'=>true, 'message'=>'Host restarted, please allow a few seconds for it to finish booting'));
                break;
            }
            case 'revert': {
                revertContainer($data->challengeId);
                echo json_encode(array('result'=>true, 'message'=>'Host reverted, please allow a few seconds for it to finish booting'));
                break;
            }
        }
    }else{
        if($gameState == Config::NOTSTARTED){
            echo json_encode(array('result'=>false, 'message'=>'The game hasn\'t started yet'));
        }elseif($gameState == Config::FINISHED){
            echo json_encode(array('result'=>false, 'message'=>'The game has finished. Thanks for playing'));
        }
    }
}else {
    require('header.php');
    ?>
    <div id="backPane" onclick="hidePopup();"></div>
    <div id="hostManagement" class="section">
				<span class="sectionHeader">
					<h2>Host Management</h2>
				</span>

        <div class="sectionContent">
            <?php
            $challenges = getChallengesForCurrentTeam($_SESSION['teamid']);
            foreach ($challenges as $challenge) {
                echo $challenge->printUserChallenge();
            }
            ?>
        </div>
    </div>


    <?php
    require('footer.php');
}
?>