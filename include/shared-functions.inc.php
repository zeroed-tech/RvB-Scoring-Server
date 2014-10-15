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
        $res = dbInsert("challenge_templates", array("name"=>$challengeName, "image"=>(strlen($imageData) == 0 ? "" : $imageData), "author"=>$challengeAuthor, "enabled"=>0, "value"=>$scoreValue));
        return ($res ? 'Challenge saved. Don\'t forget to enable it' : 'Challenge failed to save, make sure there are no issues with configuration details');
    }