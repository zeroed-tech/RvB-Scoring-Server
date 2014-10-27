<?php
	require('include/config.inc.php');

	if(isset($_POST['data'])){
		$data = json_decode($_POST['data']);
		switch ($data->functionCall) {
			case 'logout':{
				$_SESSION = array();
				if (ini_get("session.use_cookies")) {
				    $params = session_get_cookie_params();
				    setcookie(session_name("RvB"), '', time() - 42000,
				        $params["path"], $params["domain"],
				        $params["secure"], $params["httponly"]
				    );
				}
				session_destroy();
				echo json_encode(array('result'=>true, 'message'=>'You are now logged out'));
				exit();
			}
			case 'login':{
				//Select user that has the passed in teamname and password hash
				$team = dbSelect("teams", array(), array('teamname'=>$data->teamName,'password'=>md5($data->teamPassword)), false);
				//Check that a result was returned
				if(!$team || count($team) == 0){
					echo json_encode(array('result'=>false, 'message'=>'No account was found with the provided details'));
					exit();
				}else{
					if ($team[0]['enabled'] == 0) {
						echo json_encode(array('result'=>false, 'message'=>'Account is currently disabled'));
						exit();
					}
					//User is logged in. Store their teamid in the session variable
					$_SESSION['teamid'] = $team[0]['id'];
                    $_SESSION['teamName'] = $team[0]['teamname'];
					//Check to see if the team id is in the admin table
					$adminCheck = dbSelect("admins",array(),array('id'=>$_SESSION['teamid']), false);
					//If a non empty result set was returned then the user ID was found in the admins table
					$_SESSION['isAdmin'] = (!empty($adminCheck)) ? true:false;
					echo json_encode(array('result'=>true, 'message'=>'You are now logged in'));
				}
				exit();
			}
			case 'register':{
				if(strlen($data->teamName) < 1){
					echo json_encode(array('result'=>false, 'message'=>'If you cannot come up with something better than blank for your team name then try slamming your face into the keyboard and use that as your name'));
					exit();
				}

				if(strlen($data->teamPassword) < 1){
					echo json_encode(array('result'=>false, 'message'=>'You know what security professionals that use blank passwords get? <br />Fired'));
					exit();
				}

				if(dbInsert("teams", array("teamname"=>htmlspecialchars($data->teamName),"password"=>md5($data->teamPassword)))){
					//$_SESSION['teamid'] = dbSelect("teams", array("id"), array("teamname"=>$data->teamName), false)[0]['id'];
					echo json_encode(array('result'=>true, 'message'=>'You are now registered but your account is disabled. Please ask the admin to enable your account'));
					exit();
				}else{
					echo json_encode(array('result'=>false, 'message'=>'Someone has already claimed this as a teamname'));
					exit();
				}
			}
		}
	}