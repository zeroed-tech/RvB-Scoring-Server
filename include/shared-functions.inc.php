<?php
	/*
	2 = Admin
	1 = User
	0 = Guest
	*/
	function getAccessLevel(){
		if(isset($_SESSION['teamid']))
			if(isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == true)
				$accessLevel = 2;
			else
				$accessLevel = 1;
		else
			$accessLevel = 0;
		return $accessLevel;
	}


    /*
     *
     * The following are used as callback functions for the BackgroundProcess class
     *
     */
    function addContainerToDatabase($challengeName, $challengeAuthor, $scoreValue, $uploadDir){
        chdir($uploadDir);
        $imageData = null;
        if(file_exists("thumbnail.png")){
            $imageData = file_get_contents("thumbnail.png");
        }else{
            $imageData = file_get_contents("images/fake.png");
        }

        $consoleOutput = file_get_contents($uploadDir."rvbCreateContainerOutput");

        $res = dbInsert("challenge_templates", array("name"=>$challengeName, "image"=>(strlen($imageData) == 0 ? "" : $imageData), "author"=>$challengeAuthor, "enabled"=>0, "value"=>$scoreValue, "consoleOutput"=>$consoleOutput));
        return ($res ? 'Challenge saved. Don\'t forget to enable it' : 'Challenge failed to save, make sure there are no issues with configuration details');
    }

    function enableContainer($flag, $usernamePassword, $teamId, $parentId){

        dbInsert("flag", array("flag" => $flag, "enabled" => 1));
        //Read flag back from the database to get its id so we can store it with the challenge instance record
        $flagRecord = dbSelect("flag", array(), array("flag" => $flag), false)[0];
        //Add challenge instance to the database
        dbInsert("challenge_instance", array("parentId" => $parentId, "teamId" => $teamId, "flagId" => $flagRecord['id'], "username" => explode(":",$usernamePassword)[0], "password" => explode(":",$usernamePassword)[1]));

    }