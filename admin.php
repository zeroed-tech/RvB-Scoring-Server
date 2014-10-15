<?php
require('include/config.inc.php');
require('challengeManagement.php');


/**
 *    Gets a list of containers on the server and returns the results that contain $filter
 *    Note: $filter is case sensitive
 *
 *    @param $filter string A regular expression used to match container names
 *
 *    @returns array An array of containers that have a name matching $filter
 */
function getContainers($filter)
{
    $matchedContainers = array();
    $results = array();
    exec('sudo ls /var/lib/lxc', $results);
    foreach ($results as $result) {
        if (preg_match($filter, $result) == 1) {
            $matchedContainers[] = $result;
        }
    }
    return $matchedContainers;
}

function sendBroadcast($message, $type="message"){
    dbInsert("rtmsMessageQueue", array("message"=>$message,"type"=>$type, "timeAdded"=>time()));
}




//Check to see if this is a post query or page request
if (isset($_POST['data'])) {
    if (getAccessLevel() != 2) {
        echo json_encode(array('result' => 'false', 'message' => 'Only admins can call admin functions. Please login first'));
        exit();
    }
    $data = json_decode($_POST['data']);

    switch ($data->functionCall) {
        case 'broadcastMessage':{
            sendBroadcast($data->message);
            echo json_encode(array('result' => 'true', 'message' => 'Message sent'));
            break;
        }
        case 'disable':
        case 'enable': {//Enable or disable teams
            //Same function used for both enable and disable, value is calculated by checking it function is enable or disable
            $updateArray = array("enabled" => (strcmp($data->functionCall, "enable") == 0 ? 1 : 0));
            if ($data->functionCall == "enable") {
                $updateArray["type"] = $data->teamType;
            }
            $res = dbUpdate("teams", $updateArray, array("id" => $data->teamId));

            echo json_encode(array('result' => ($res == null ? 'true' : 'false'), 'message' => 'Team ' . (strcmp($data->functionCall, "enable") == 0 ? "enabled" : "disabled")));//If the above update returns null then something went wrong
            break;
        }
        case 'makeAdmin': {
            $res = null;
            if ($data->revoke)
                $res = dbDelete("admins", array("id" => $data->teamId));
            else
                $res = dbInsert("admins", array("id" => $data->teamId));
            echo json_encode(array('result' => ($res ? 'true' : 'false'), 'message' => ($data->revoke ? "Admin rights revoked" : "Admin rights granted")));
            break;
        }
        case 'deleteTeam': {
            dbDelete("admins", array("id" => $data->teamId));
            $res = dbDelete("teams", array("id" => $data->teamId));
            echo json_encode(array('result' => ($res ? 'true' : 'false'), 'message' => "Team Deleted"));
            break;
        }
        case 'resetPassword': {
            //Reset the specified teams password
            $res = dbUpdate("teams", array("password" => md5($data->newPassword)), array("id" => $data->teamId));
            echo json_encode(array('result' => ($res ? 'true' : 'false'), 'message' => ($res ? 'Password Reset' : 'Password couldn\'t be reset. Make sure your still logged in and try again')));
            break;
        }
        case 'getBaseContainers': {
            //Gets all base containers and returns them as a json object
            echo json_encode(getContainers("/-Base/"));
            break;
        }
        case 'addChallenge': {
            //Add a new challenge

            //Verify the data passed in by the user
            if (strlen($data->challengeName) == 0) {
                echo json_encode(array('result' => 'false', 'message' => 'Invalid name entered'));
                exit();
            }
            if (strlen($data->challengeAuthor) == 0) {
                echo json_encode(array('result' => 'false', 'message' => 'Invalid author entered'));
                exit();
            }
            if (strlen($data->scoreValue) == 0 || !is_numeric($data->scoreValue)) {
                echo json_encode(array('result' => 'false', 'message' => 'Invalid score entered'));
                exit();
            }

            //Verify that a file was uploaded along with the form data
            if (isset($_FILES['challengeFiles']) && $_FILES['challengeFiles']['error'] == 'UPLOAD_ERROR_OK') {
                //Create a random folder name
                $uploadDir = "/var/www/uploads/" . uniqid() . "/";
                $fileName = $_FILES["challengeFiles"]["name"];
                $fileLocation = $uploadDir . $fileName;
                //Create a folder in uploads with the generated random name
                mkdir($uploadDir, 0700, true);

                //Verify that the file is the correct format
                switch (strtolower($_FILES['challengeFiles']['type'])) {
                    case 'application/x-gzip':
                    case 'application/x-tar':
                    case 'application/zip':
                    case 'application/x-bzip2':
                    case 'application/octet-stream'://Used to detect rar files
                    case ''://HTTP file API doesn't appear to recognise 7zip files so we have to accept blank
                        break;
                    default:
                        echo json_encode(array('result' => 'false', 'message' => 'Unsupported file type: ' . $_FILES['challengeFiles']['type'] . '. Please upload a compressed file'));
                        exit();
                }

                //Move uploaded file to our new random temp dir
                if (!move_uploaded_file($_FILES['challengeFiles']['tmp_name'], $fileLocation)) {
                    echo json_encode(array('result' => 'false', 'message' => 'Failed to move your file to the temporary uploads directory. Check that /var/www/uploads is writable by www-data'));
                    exit();
                }

                //Change the current working directory to our random uploads dir
                chdir($uploadDir);
                //Extract the uploaded file
                exec('extract ' . $fileName);
                //Delete the uploaded zip file
                //unlink($fileName);

                $command = "/usr/bin/rvbCreateContainer ".$uploadDir." ".$data->baseImage." ".preg_replace("/[^A-Za-z0-9]/", '', $data->challengeName);

                $callbackArgs = array($data->challengeName, $data->challengeAuthor, $data->scoreValue, $uploadDir);

                $bp = new BackgroundProcess($command, "addContainerToDatabase", $callbackArgs);
                $bp->run("/tmp/rvbScriptOutput");

                $_SESSION['BGProcess'] = serialize($bp);

                //$_SESSION['containervals_uploaddir'] = $uploadDir;
                //$_SESSION['containervals_name'] = $data->challengeName;
                //$_SESSION['containervals_author'] = $data->challengeAuthor;
                //$_SESSION['containervals_points'] = $data->scoreValue;
                //$_SESSION['containervals_baseimage'] = $data->baseImage;

                echo json_encode(array('result' => 'true', 'message' => 'Challenge added to background queue'));
            } else {
                echo json_encode(array('result' => 'false', 'message' => 'A challenge file was not uploaded with the challenge details'));
            }
            break;
        }
        case 'editChallenge': {
            if (isset($data->challengeAuthor)) {//If this is set then they are changing the challenges details, otherwise they are requesting the challenges details so they can edit them later

                //Verify the data passed in by the user
                if (strlen($data->challengeAuthor) == 0) {
                    echo json_encode(array('result' => 'false', 'message' => 'Invalid author entered'));
                    exit();
                }
                if (strlen($data->scoreValue) == 0 || !is_numeric($data->scoreValue)) {
                    echo json_encode(array('result' => 'false', 'message' => 'Invalid score entered'));
                    exit();
                }

                $res = dbUpdate("challenge_templates", array("author" => $data->challengeAuthor, "value" => $data->scoreValue), array("id" => $data->challengeId));

                error_log($res);
                echo json_encode(array('result' => $res, 'message' => ($res ? "Challenge updated" : "An error occurred whilst updating the challenge details. Check the PHP error log for assistance in debugging this issue")));
            } else {
                $res = dbSelect("challenge_templates", array(), array("id" => $data->challengeId), false)[0];
                echo json_encode(array("challengeName" => $res['name'], "challengeAuthor" => $res['author'], "scoreValue" => $res['value']));
            }
            break;
        }
        case 'enableChallenge': {//Enable challenges // TODO add error message
            enableChallenge($data->challengeId);
            echo json_encode(array('result' => 'true', 'message' => 'Challenge ' . "enabled"));
            break;
        }
        case 'disableChallenge': {
            disableChallenges($data->challengeId);
            echo json_encode(array('result' => 'true', 'message' => 'Challenge disabled'));
            break;
        }
        case 'deleteChallenge': {//Delete challenges from the database and delete all containers based off this challenge
            deleteChallenge($data->challengeId);
            break;
        }
        case 'updateConfig': {
            //Update game config
            $res = dbUpdate("config", array("startTime" => $data->startTime, "endTime" => $data->endTime, "motd" => $data->motd, "leftHeader" => $data->leftHeader, "centerHeader" => $data->centerHeader, "rightHeader" => $data->rightHeader), array("id" => 1));
            echo json_encode(array('result' => ($res == null ? 'true' : 'false')));
            break;
        }
        //The following functions are direct passthroughs to the lxc tools on the VM and may cause damage if used incorrectly
        case 'startContainer': {
            exec("sudo lxc-start -d -n " . escapeshellcmd($data->containerName));
            break;
        }
        case 'stopContainer': {
            exec("sudo lxc-stop -n " . escapeshellcmd($data->containerName));
            break;
        }
        case 'deleteContainer': {
            exec("sudo lxc-stop -n " . escapeshellcmd($data->containerName));
            exec("sudo lxc-destroy -n " . escapeshellcmd($data->containerName));
            break;
        }
        case 'regenFlag': {
            setFlagForContainer($data->containerName);
            break;
        }
    }
} else {
    if (getAccessLevel() != 2) {
        header("location: index.php");
        exit();
    }
    //This is a page request
    require('header.php');
    echo "<div id='backPane' onclick='hidePopup();'></div>";
        ?>
    <input type="text" id="broadcastMessage" /><input id="sendBroadcast" type="submit">
        <div id="usermanagement" class="section">
            <h2 class="sectionHeader">Team Management</h2>

            <div class="sectionContent">
                <table class="adminTables">
                    <tr>
                        <td>Team Id</td>
                        <td>Team Name</td>
                        <td>Enabled</td>
                        <td>Team Type</td>
                        <td>Admin</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php
                    $users = getUserList();

                    foreach ($users as $user) {
                        echo "<tr>";
                        echo "<td>" . $user['id'] . "</td>";
                        echo "<td>" . $user['teamname'] . "</td>";
                        echo "<td>" . ($user['enabled'] == 1 ? "Enabled" : "Disabled") . "</td>";
                        if($user['isAdmin'] == 1){
                            echo "<td>Admin</td>";
                            echo "<td>True</td>";
                        }else {
                            echo "<td>" . ($user['type'] == 0 ? "Admin" : ($user['type'] == 1 ? "Red" : ($user['type'] == 2) ? "Blue" : ($user['type'] == 3 ? "Purple" : "NA"))) . "</td>";
                            echo "<td>False</td>";
                        }
                        echo "<td><a href='jscript:void(0)' class='enableTeam tableButtons " . ($user['enabled'] == 1 ? "red" : "green") . "'>" . ($user['enabled'] == 1 ? "Disable" : "Enable") . " Team </a></td>";
                        echo "<td><a href='jscript:void(0)' class='makeAdmin tableButtons " . ($user['isAdmin'] == 1 ? "red" : "green") . "'>" . ($user['isAdmin'] == 1 ? "Revoke Admin" : "Promote to Admin") . "</a></td>";
                        echo "<td><a href='jscript:void(0)' class='deleteTeam tableButtons red'>Delete Team</a></td>";
                        echo "<td><a href='jscript:void(0)' class='resetPassword tableButtons blue'>Reset Password</a></td>";
                        echo "</tr>";
                    }

                    ?>
                </table>
            </div>
        </div>

        <div id="challengemanagement" class="section">
            <h2 class="sectionHeader">Challenge Management</h2>

            <div class="sectionContent">
                <a href='jscript:void(0)' onclick="addChallenge()" id='addChallenge' class='modifyButton'>Add
                    Challenge</a><br/><br/>
                <?php
                $challenges = getChallengeList();
                foreach ($challenges as $challenge) {
                    echo $challenge->printChallenge();
                }
                ?>
            </div>
        </div>

        <div id="challengeconfiguration" class="section">
            <h2 class="sectionHeader">Challenge Configuration</h2>

            <div class="sectionContent">

                <h3>Time Management</h3><br/>
                <label for="startTime">Start Time:</label>
                <input class='configInput' type="text" id="startTime" value="<?php echo "$CONFIG->startDate"; ?>"/><br/>
                <label for="endTime">End Time:</label>
                <input class='configInput' type="text" id="endTime" value="<?php echo "$CONFIG->endDate"; ?>"/><br/>
                <hr/>
					<span class='tacontainer'>
						<label for="motd_edit">Message of the day:</label>
						<textarea id="motd_edit"><?php echo "$CONFIG->motd"; ?></textarea><br/>
					</span>
                <hr/>
                <h3>Header Configuration</h3>
                <button style="margin-top: 25px;float:right;" onclick="resetStyles();">Reset Styles</button>
                <br/>
                <label for"leftHeaderSet">Left Word:</label>
                <input class='configInput' type="text" id="leftHeaderSet"
                       value="<?php echo explode('<><>', $CONFIG->leftHeader)[0]; ?>"/>
					
					<span class='tacontainer'>
						<label for="leftHeaderStyle">Style:</label>
						<textarea id="leftHeaderStyle"><?php echo explode('<><>', $CONFIG->leftHeader)[1]; ?></textarea><br/>
					</span>

                <label for="centerHeaderSet">Center Word:</label>
                <input class='configInput' type="text" id="centerHeaderSet"
                       value="<?php echo explode('<><>', $CONFIG->centerHeader)[0]; ?>"/>
					<span class='tacontainer'>
						<label for"centerHeaderStyle">Style:</label>
                        <textarea
                            id="centerHeaderStyle"><?php echo explode('<><>', $CONFIG->centerHeader)[1]; ?></textarea><br/>
					</span>


                <label for="rightHeaderSet">Right Word:</label>
                <input class='configInput' type="text" id="rightHeaderSet"
                       value="<?php echo explode('<><>', $CONFIG->rightHeader)[0]; ?>"/>
					
					<span class='tacontainer'>
						<label for="rightHeaderStyle">Style:</label>
						<textarea
                            id="rightHeaderStyle"><?php echo explode('<><>', $CONFIG->rightHeader)[1]; ?></textarea><br/>
					</span>
                <button style="float:right; margin-top:-20px;" onclick="updateGameRules();">Update Rules</button>
            </div>
        </div>
        <div id="containerManagement" class="section">
            <h2 class="sectionHeader">Container Management</h2>

            <div class="sectionContent">
                <h4>The following functions are intended to be used as a last resort and may cause the database to
                    reference containers that no longer exist</h4>
                <table class="adminTables">
                    <tr>
                        <td>Name</td>
                        <td>State</td>
                        <td>IP</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php
                    $res = array();
                    exec("sudo lxc-ls --fancy", $res);
                    foreach (array_slice($res, 2) as $container) {
                        if (preg_match("/-Base/", $container) == 1) {
                            continue;
                        }
                        //Remove double spaces
                        $container = preg_replace('/\s+/', ' ', $container);
                        //Split on space character
                        $row = explode(' ', $container);
                        //Print table of container states
                        echo "<tr>";
                        echo "<td class='containerName'>" . $row['0'] . "</td>";
                        echo "<td>" . $row['1'] . "</td>";
                        echo "<td>" . $row['2'] . "</td>";
                        echo "<td><a href='jscript:void(0)' class='green startContainer'>Start Container</a></td>";
                        echo "<td><a href='jscript:void(0)' class='red stopContainer'>Stop Container</a></td>";
                        echo "<td><a href='jscript:void(0)' class='red deleteContainer'>Delete Container</a></td>";
                        echo "<td><a href='jscript:void(0)' class='blue regenFlag'>Regenerate Flag</a></td>";
                        echo "</tr>";
                    }

                    echo "</table>";
                    ?>
            </div>
        </div>
    <?php

    require('footer.php');
}
?>