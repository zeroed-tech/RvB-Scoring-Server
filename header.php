
<html>
	<head>
		<meta charset="utf-8">
			<link rel="stylesheet" type="text/css" href="css/header.php" />
			<link rel="stylesheet" type="text/css" href="css/style.css" />
			<script src="js/jquery-2.1.1.min.js"></script>
			<script src="js/script.js"></script>

			<!-- Include jquery forms just to make things a bit easier when communicating with the server -->
			<script src="js/jquery.form.js"></script>
			<!-- Include toastr files -->
			<link rel="stylesheet" type="text/css" href="css/toastr.min.css" />
			<script src="js/toastr.min.js"></script>

			<?php 
			if(getAccessLevel() == 2){
				//Only include admin functions if the current user is an admin.
				//The backend of the site will still verify that users are actually admins before running functions so it doesn't matter if someone finds this
				echo "<script src='js/admin.js'></script>";
				
				//Include the admin CSS file. Nothing secret here, it just keeps the main CSS file cleaner
				echo "<link rel='stylesheet' type='text/css' href='css/admin.css' />";
			}

			?>
			<script src="js/polar-clock.js"></script>
			<title>RvB</title>
			<meta http-equiv="content-type" content="text/html;charset=utf-8" />
			<meta name="author" content="Adrian Justice, Nathan Buck, Steven Morris, Rob McIntosh" />  
	</head>
	<body>
		<div id="header">
		    <?php
		    	echo $CONFIG->buildHeaderContent();
		    ?>
		</div>
		<div id="navbar">
			<a href="index.php">Home</a>
			<a href="javascript:void(0)" onclick="scoreboard();">Scoreboard</a>
			<a href="javascript:void(0)" onclick="rules();">Rules</a>
			<?php
            $teamType = -1;
			switch (getAccessLevel()) {
				case 0:
					echo "<a href='javascript:void(0)' onclick='login();'>Login</a>";
					echo "<a href='javascript:void(0)' onclick='register();'>Register</a>";
					break;
				case 1:
                    $teamType = getTeamType();
                    if($teamType == null){
                        session_destroy();
                    }
                    if($teamType == 2 || $teamType == 3) {//Blue or Purple team
                        echo "<a href='manage.php#hostManagement'>Management</a>";
                    }
					if($teamType == 1 || $teamType == 3) {//Red or Purple team
                        echo "<a href='achievements.php'>Achievements</a>";
                        echo "<a href='javascript:void(0)' onclick='submitFlag();'>Submit Flag</a>";
                    }

                    echo "<a href='javascript:void(0)' onclick='logout();'>Logout</a>";
                    echo "<a href='javascript:void(0)' onclick='void(0);'></a>";


					break;
				case 2:
					echo "<a href='javascript:void(0)' onclick='logout();'>Logout</a>";
					echo "<a href='admin.php'>Admin</a>";
					break;
			}
            echo "<hr />";
            /*
            $gameState = $CONFIG->getGameState();
            if($gameState == Config::RUNNING){
                    echo "<h4 class='debug'>RUNNING   ".date('Y-m-d H:i:s')."</h4>";
            }elseif($gameState == Config::NOTSTARTED){
                echo "<h4 class='debug'>NOT STARTED   ".date('Y-m-d H:i:s')."</h4>";
            }elseif($gameState == Config::FINISHED){
                echo "<h4 class='debug'>FINISHED   ".date('Y-m-d H:i:s')."</h4>";
            }*/
            if($teamType == 1 || $teamType == 3) {//Red or Purple team
                $team = dbRaw("SELECT t.id as tid, t.teamname, SUM(DISTINCT(ct.value)) as currentScore FROM teams AS t INNER JOIN attempts AS a ON t.id=a.teamid AND a.correct=1 INNER JOIN flag AS f ON a.flagSubmitted = f.flag INNER JOIN challenge_instance AS c ON c.flagId = f.id INNER  JOIN challenge_templates AS ct ON ct.id = c.parentId WHERE t.id=" . $_SESSION['teamid'] . " GROUP BY t.id ORDER BY currentScore DESC");
                if (count($team) == 0) {
                    $team[0]['teamname'] = "";
                    $team[0]['currentScore'] = "";
                }

                echo "<a href='javascript:void(0)' class='score' onclick='void(0);'>" . $team[0]['teamname'] . "      " . $team[0]['currentScore'] . "</a>";
                echo "<br />";
            }

            ?>

		</div>