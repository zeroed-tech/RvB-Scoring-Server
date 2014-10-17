<?php

/**
 * Clones the specified base container
 *
 * @param $template string Base container name
 * @param $containerName string New container name
 */
function createContainerFromTemplate($template, $containerName){
    exec("sudo lxc-clone -s ".$template." ".$containerName, $res);
}

/**
 * "Powers on" the specified container
 *
 * @param $containerName string The container to start
 */
function startContainer($containerName){
    exec("sudo lxc-start -d -n ".$containerName);
    sleep(10);
}

/**
 * "Powers off" the specified container
 *
 * @param $containerName string The container to stop
 */
function stopContainer($containerName){
    error_log("Running sudo lxc-stop -n ".$containerName);
    $res = array();
    exec("sudo lxc-stop -n ".$containerName, $res);
    foreach ($res as $value) {
        error_log($value);
    }
}

/**
 * Deletes  the specified container
 *
 * @param $containerName string The container to delete
 * @param null $challengeInstance array The database row for this challenge instance containing all associated ids
 */
function deleteContainer($containerName, $challengeInstance=null){
    $res = array();
    //Stop the container (doesn't matter if it was already stopped)
    stopContainer($containerName);
    //Delete the container
    error_log("Deleting container");
    exec("sudo lxc-destroy -n ".$containerName, $res);
    foreach ($res as $value) {
        error_log($value);
    }
    //error_log("Clearing DNS:");
    //Delete the reference to the container from the DDNS server
    //exec('/bin/echo -e "update delete '.$containerName.'.rvb\nsend" | nsupdate -y DHCP_UPDATER:ExmzbSwSy1oOJNIFVkbHew==');

    //Delete the SSH fingerprint from the known hosts file
    exec('sudo ssh-keygen -f "/root/.ssh/known_hosts" -R '.$containerName);

    if($challengeInstance!=null) {
        //Delete container instance from the database
        dbDelete("challenge_instance", array("id" => $challengeInstance['id']));

        //Disable the flag for this challenge
        dbUpdate("flag", array("enabled" => 0), array("id" => $challengeInstance['flagId']));
    }
}

function createContainerInstanceFromTemplate($teamId, $parentChallenge){
    //Team doesn't have a challenge instance for this challenge so we need to make one
    //Strip out the special characters from the challenge name and prepend the teams id the the container name to create a new container name
    $containerName = preg_replace("/[^A-Za-z0-9]/", '', $parentChallenge['name']);
    $newContainerName = 'Team' . $teamId . '-' . $containerName;

    //Create a new container and start it so we can configure it
    createContainerFromTemplate($containerName, $newContainerName);
    startContainer($newContainerName);

    //Setup flag in container
    $flag = setFlagForContainer($newContainerName);
    //Add flag to the database
    dbInsert("flag", array("flag" => $flag, "enabled" => 1));
    //Read flag back from the database to get its id so we can store it with the challenge instance record
    $flagRecord = dbSelect("flag", array(), array("flag" => $flag), false)[0];

    //Setup a user in the container so the user can access the system
    $usernamePassword = setUsernameAndPassword($newContainerName, $teamId);

    //Add challenge instance to the database
    dbInsert("challenge_instance", array("parentId" => $parentChallenge['id'], "teamId" => $teamId, "flagId" => $flagRecord['id'], "username" => explode(":",$usernamePassword)[0], "password" => explode(":",$usernamePassword)[1]));

    if(getConfig()->getGameState() != Config::RUNNING) {
        //Shutdown the container (don't need it until the game starts)
        stopContainer($newContainerName);
    }
}

function revertContainer($containerId){
    //Recover challenge instance and parent details from the database before deleting them
    $challengeInstance = dbSelect("challenge_instance", array(), array("id"=>$containerId),false)[0];
    $challengeParent = dbSelect("challenge_templates", array(), array("id"=>$challengeInstance['parentId']), false)[0];
    $containerName = $challengeInstance['username']."-".$challengeParent['name'];
    //Delete the old container instance
    deleteContainer($containerName, $challengeInstance);
    error_log("Creating new instance");

    //Delay until the server has forgotten the host we just deleted
    sleep(30);

    //Create a new instance of the container
    createContainerInstanceFromTemplate($challengeInstance['teamId'], $challengeParent);
}

function getRandomWord(){
    $file = new SplFileObject("/var/www/html/include/dictionary.txt");
    $numOfLines = 349900;//Number of lines in the dictionary
    $file->seek(rand(0,$numOfLines));
    return trim(ucfirst($file->fgetss()));
}

/**
 * Generates a random flag by selecting 4 random dictionary words, sets the flag in the specified container and returns the generated flag.
 *
 * @return string The flag that was set in the container
 */
function setFlagForContainer(){
    //Read 4 random words from the dictionary file
    $flag = "FLAG:{".getRandomWord().getRandomWord().getRandomWord().getRandomWord()."}";
    //error_log($flag);
    //$res = array();
    //exec("sudo ssh rvbadmin@".$containerName." -o StrictHostKeyChecking=no -o ConnectTimeout=5 -i /home/rvbadmin/.ssh/id_rsa -t configureFlag ".$flag, $res);
    //foreach ($res as $value) {
    //  error_log($value);
    //}
    return $flag;
}

/**
 * Creates and sets a username and password in the specified container.
 *
 * @param $teamId string The team id that should be used to create this container's credentials
 * @return string The username and password set on the specified container in the format username:password
 */
function setUsernameAndPassword($teamId){
    $username = "Team".$teamId;
    $password = substr(str_shuffle(getRandomWord().getRandomWord()),0,20);//Get 2 random words then shuffle the letters
    //$res = array();
    //exec("sudo ssh rvbadmin@".$containerName." -o StrictHostKeyChecking=no -o ConnectTimeout=5 -i /home/rvbadmin/.ssh/id_rsa -t configureUsers ".$username." ".$password, $res);
    //foreach ($res as $value) {
    //    error_log($value);
    //}
    return $username.":".$password;
}


/**
 * Enables the specified challenge and generates a new instance for any teams that have been created since the last time this challenge was enabled.
 * @param $challengeId string The id of the challenge to enable
 */
function enableChallenge($challengeId){
    //Set challenge to enabled. This enables all instances of this challenge
    dbUpdate("challenge_templates", array("enabled" => 1), array("id" => $challengeId));

    //Get the information about the challenge we are enabling
    $challenge = dbSelect("challenge_templates", array(), array("id" => $challengeId), false)[0];

    //Get a list of teams
    $teams = getTeamList();
    foreach ($teams as $team) {
        //Make sure the user is enabled and is either a blue or purple team
        if ($team['enabled'] == 1 && ($team['type'] == 2 || $team['type'] == 3)) {
            //Make sure this isn't an admin and continue if it is (admins don't need a challenge instance)
            if($team['isAdmin']){
                continue;
            }

            //Check to see if a challenge instance has already been created for this user
            $chalInstance = dbSelect("challenge_instance",array(),array("teamId"=>$team["id"], "parentId"=>$challengeId),false);
            if(sizeof($chalInstance) == 0) {
                //Team doesn't have a challenge instance for this challenge so we need to make one
                //Strip out the special characters from the challenge name and prepend the teams id the the container name to create a new container name
                $baseContainer = preg_replace("/[^A-Za-z0-9]/", '', $challenge['name']);
                $newContainerName = 'Team' . $team['id'] . '-' . $baseContainer;
                $flag = setFlagForContainer();
                $usernamePassword = setUsernameAndPassword($team['id']);
                $username = explode(':',$usernamePassword)[0];
                $password = explode(':',$usernamePassword)[1];
                $shutdown = (getConfig()->getGameState() == Config::RUNNING ? "true":"false");

                $command = "/usr/bin/rvbEnableContainer $baseContainer $newContainerName $flag $username $password $shutdown";

                $callbackArgs = array($flag, $usernamePassword, $team['id'], $challenge['id']);

                $bp = new BackgroundProcess($command, "enableContainer", $callbackArgs);
                $bp->run("/tmp/$newContainerName/rvbEnableContainerOutput");

                $_SESSION['BGProcess'] = serialize($bp);

            }else{
                error_log("Not creating challenge instance of: ".$challenge['name']." for team: ".$team['teamname']." as they already have one.");
            }
        }
    }
}

function disableChallenges($challengeId){
    $chal = dbSelect("challenge_templates", array(), array('id'=>$challengeId), false);
    if(count($chal) > 0) {//Check to make sure the challenge actually exists
        $challengeName = $chal[0]['name'];
        //$sanitisedChallengeName = preg_replace("/[^A-Za-z0-9]/", '', $challengeName);
        $sanitisedChallengeName = createSanitisedChallengeName($challengeName);
        $containersToDisable = getContainers("/^(Team\d*-)*" . $sanitisedChallengeName . "(-Base)*$/");

        foreach ($containersToDisable as $container) {
            stopContainer($container);
        }
    }

    dbUpdate("challenge_templates", array("enabled"=>0), array("id"=>$challengeId));
}

function deleteChallenge($challengeId){
    //Get the challenge record from the database before we delete it so we have access to the challenge name
    $chal = dbSelect("challenge_templates", array(), array('id'=>$challengeId), false);
    if(count($chal) > 0) {//Check to make sure the challenge actually exists
        $sanitisedChallengeName = createSanitisedChallengeName($chal[0]['name']);
        //Get a list of containers that match the base containers name
        $containersToDelete = getContainers("/^(Team\d*-)*".$sanitisedChallengeName."(-Base)*$/");
        foreach ($containersToDelete as $container) {
            error_log("Going to delete ".$container);
            //Stop the container if its running
            deleteContainer($container);
        }
        //Delete the challenge from the database
        dbDelete("challenge_templates", array("id"=>$challengeId));
        $flagsToDelete = dbSelect("challenge_instance", array(), array("parentId"=>$challengeId), false);
        foreach($flagsToDelete as $flag){
            //Disable all flags who's challenges are being deleted
            dbUpdate("flag",array("enabled"=>0),array("id"=>$flag['flagId']));
        }
        //Delete challenge template
        dbDelete("challenge_templates", array("id"=>$challengeId));
        //Delete all instances
        dbDelete("challenge_instance",array("parentId"=>$challengeId));

        echo json_encode(array('result'=>"true", 'message'=>'Challenge deleted'));
    }
}

function getChallengeWithId($chalId){
    return dbSelect("challenge_templates", array(), array('id'=>$chalId),false)[0];
}

function createSanitisedChallengeName($challengeName, $forTeam=null){
    $sanitisedChallengeName = preg_replace("/[^A-Za-z0-9]/", '', $challengeName);
    if($forTeam!=null){
        $sanitisedChallengeName = "Team".$forTeam."-".$sanitisedChallengeName;
    }
    return $sanitisedChallengeName;
}