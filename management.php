<?php
require('include/config.inc.php');
if(isset($_POST['data'])){
    if (!(getAccessLevel() == 1 && (getTeamType() == 1 || getTeamType() == 3))) {
        echo json_encode(array('result' => 'false', 'message' => 'You must be logged in and a member of either the Red or Purple team to perform this action'));
        exit();
    }

    $gameState = $CONFIG->getGameState();
    if($gameState == Config::RUNNING) {
        $data = json_decode($_POST['data']);

        switch ($data->functionCall) {
            case 'submitFlag': {
                //Check to see if the flag exists in the database and that it doesn't belong to the submitting team
                //$flags = dbSelect("flag", array(), array('flag'=>$data->flag), false)
                $flags = dbRaw("SELECT f.flag, f.enabled AS fenabled, ct.enabled as cenabled, t.enabled as tenabled, ct.name, ct.value, t.teamname, t.id FROM challenge_instance as ci INNER JOIN flag AS f ON ci.flagId = f.id INNER JOIN challenge_templates AS ct ON ci.parentId = ct.id INNER JOIN teams AS t ON ci.teamId = t.id");
                $flagCaptured = false;
                if (count($flags) == 0) {
                    echo json_encode(array('result' => false, 'message' => 'Sorry, this flag is invalid'));
                } else {
                    $flagMatch = false;
                    foreach ($flags as $flag) {
                        if ($data->flag == $flag['flag'] || $data->flag == substr($flag['flag'], 6, strlen($flag['flag']) - 7)) {
                            //Check that the flag, user and challenge that the submitted flag belongs to are actually enabled
                            if ($flag['id'] == $_SESSION['teamid']) {
                                echo json_encode(array('result' => false, 'message' => 'Nice try but you cannot capture your own flag'));
                                $flagMatch = true;
                                break;
                            }
                            if ($flag['fenabled'] != true) {
                                echo json_encode(array('result' => false, 'message' => 'This flag was once correct but has since been revoked. Please reacquire the flag and try again'));
                                $flagMatch = true;
                                break;
                            }
                            if ($flag['cenabled'] != true) {
                                echo json_encode(array('result' => false, 'message' => 'This flag is correct but the challenge that it belongs to is currently disabled. When the admin re-enables the challenge you will be able to resubmit.'));
                                $flagMatch = true;
                                break;
                            }
                            if ($flag['tenabled'] != true) {
                                echo json_encode(array('result' => false, 'message' => 'This flag is correct but the team it belongs to has been disqualified or is currently disabled. Until this team is re-enabled this flag will remain disabled'));
                                $flagMatch = true;
                                break;
                            }
                            echo json_encode(array('result' => true, 'message' => 'Flag captured, congratulations'));
                            $flagCaptured = true;
                            $flagMatch = true;
                            break;
                        }
                    }
                    if(!$flagMatch){
                        echo json_encode(array('result' => false, 'message' => 'Sorry, this flag is invalid'));
                    }
                }

                //Submit the flag to the database as an attempt. This lets us print some interesting stats at the end.
                //Doesn't matter if the same correct flag is submitted over and over, this will be filtered out on the scoreboard side
                dbInsert("attempts", array("teamid" => $_SESSION['teamid'], "flagSubmitted" => $data->flag, "timeSubmitted" => date_create()->format('Y-m-d H:i:s'), "correct" => $flagCaptured));

                break;
            }

            case 'getScoreboard': {
                //Setup an empty array to hold each of the score entries
                $res = array();
                //Select each teams total from the database
                $scores = dbRaw("SELECT t.id as tid, t.teamname, SUM(DISTINCT(ct.value)) as currentScore FROM teams AS t INNER JOIN attempts AS a ON t.id=a.teamid AND a.correct=1 INNER JOIN flag AS f ON a.flagSubmitted = f.flag INNER JOIN challenge_instance AS c ON c.flagId = f.id INNER  JOIN challenge_templates AS ct ON ct.id = c.parentId GROUP BY t.id ORDER BY currentScore DESC");

                //Loop through the teams scores and build a json object to return
                foreach ($scores as $score) {
                    $res[] = array('teamName' => $score['teamname'], 'currentScore' => $score['currentScore'], 'currentTeam' => ($score['tid'] == $_SESSION['teamid'] ? true : false));
                }
                //Return the json encoded scores
                echo json_encode($res);
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
		
}else{
	echo json_encode(array('result'=>false, 'message'=>'Invalid API call'));
}
