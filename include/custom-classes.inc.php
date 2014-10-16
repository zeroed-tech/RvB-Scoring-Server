<?php
class Challenge{
	private $challengeId;
	private $challengeName = '';
	private $challengeImage = '';
	private $challengeAuthor = '';
	private $isEnabled;
    private $username;
    private $password;
    private $ip;
    private $points;

    /**
     * @param $challengeId string Value read from the database
     * @param $challengeName string Value read from the database
     * @param $challengeImage string Value read from the database
     * @param $challengeAuthor string Value read from the database
     * @param $isEnabled string Value read from the database
     * @param null $username string Value read from the database
     * @param null $password string Value read from the database
     * @param null $teamId string The id of the team this matches. Used in regex scripts to find IP addresses for containers
     */
	public function __construct($challengeId, $challengeName, $challengeImage, $challengeAuthor, $isEnabled, $points, $username=null,$password=null, $teamId=null){
		$this->challengeId = $challengeId;
		$this->challengeName = $challengeName;
        $this->points = $points;
		if($challengeImage != null){
			$this->challengeImage = $challengeImage;
		}else{
            $this->challengeImage = base64_encode(file_get_contents("images/fake.png"));
        }
		$this->challengeAuthor = $challengeAuthor;
		$this->isEnabled = $isEnabled;
        if($username!=null){
            $this->username = $username;
        }
        if($password!=null){
            $this->password = $password;
        }

        if($teamId!=null) {
            $results = array();
            exec('sudo lxc-ls --fancy -F name,ipv4 "^(Team' . $teamId . '-)+'.$challengeName.'(-Base)*$"', $results);
            if(count($results) != 3){
                error_log("Wrong number of results:");
                foreach ($results as $result) {
                    error_log($result);
                }
            }else{
                $result = $results[2];
                $this->ip = explode(' ',preg_replace('/\s+/', ' ', $result))[1];

            }
        }
	}

    /**
     * This function generates and returns an HTML representation of this object
     *
     * @return string The complete HTML required to display this challenge to an admin
     */
	public function printChallenge(){
		$html = "<div class='challenge ".($this->isEnabled == 1 ? "enabled":"disabled")."'>";
		$html .= "<div id='challenge_".$this->challengeId."'>";
        $html .= "<a href='javascript:void(0)' class='delete'>X</a>";
		$html .= "<span id='a'>";
		$html .= "<h4>".substr($this->challengeName, 0, 70)."</h4>";
		$html .= "</span>";
		$html .= "<img src='data:image/jpeg;base64,".$this->challengeImage."' ></img>";
		$html .= "<ul>";
		$html .= "<li class='author'>Author: ".$this->challengeAuthor."</li>";
        $html .= "<li class='points'>Points: ".$this->points."</li>";
		$html .= "</ul>";
		$html .= "<a href='javascript:void(0)' class='modifyButton enable'>Enable</a>";
		$html .= "<a href='javascript:void(0)' class='modifyButton disable'>Disable</a><br />";
		$html .= "<a href='javascript:void(0)' class='modifyButton edit'>Edit</a>";
        $html .= "<a href='javascript:void(0)' class='modifyButton consoleOutput'>Output</a>";
		$html .= "</div>";
		$html .= "</div>";
		return $html;
	}

    /**
     * This function generates and returns an HTML representation of this object
     *
     * @return string The complete HTML required to display this challenge to a user
     */
    public function printTeamChallenge(){
        $html = "<div class='challenge ".($this->isEnabled == 1 ? "enabled":"disabled")."'>";
        $html .= "<div id='challenge_".$this->challengeId."'>";
        $html .= "<span>";
        $html .= "<h4>".substr($this->challengeName, 0, 50).(strlen($this->challengeName) > 50 ? "...":"")."</h4>";
        $html .= "</span>";
        $html .= "<img src='data:image/jpeg;base64,".$this->challengeImage."' ></img>";
        $html .= "<ul>";
        $html .= "<li class='author'>Author: ".$this->challengeAuthor."</li>";
        $html .= "<li class='username'>Username: ".$this->username."</li>";
        $html .= "<li class='password'>Password: ".$this->password."</li>";
        $html .= "<li class='ip'>IP: ".($this->ip != null && strcmp($this->ip, '-') != 0 ? $this->ip:"Powered Off" )."</li>";
        $html .= "</ul>";
        $html .= "<a href='javascript:void(0)' class='modifyButton restart blue'>Restart</a>";
        $html .= "<a href='javascript:void(0)' class='modifyButton revert red'>Revert</a><br />";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }
}

class Achievement{
    public $challengeTitle;
    public $dateTimeSolved;
    public $targetName;
    public $image;
    public $points;

    public function __construct($challengeTitle, $dateTimeSolved, $targetName, $image, $points){
        $this->challengeTitle = $challengeTitle;
        $this->dateTimeSolved = $dateTimeSolved;
        $this->targetName = $targetName;
        if($image != null){
            $this->image = $image;
        }else{
            $this->image = base64_encode(file_get_contents("images/fake.png"));
        }
        $this->points = $points;
    }

    /**
     * This function generates and returns an HTML representation of this object
     *
     * @return string The complete HTML required to display this achievement to a user
     */
    public function printAchievement(){
        $html = "";
        $html .= "<div class='achievement'>";
        $html .= "<div class='achievementLogo'>";
        $html .= "<img src='data:image/jpeg;base64,".$this->image . "' ></img>";
        $html .= "<h1>$this->points</h1>";
        $html .= "</div>";
        $html .= "<div class='achievementDetails'>";
        $solved = explode(" ",$this->dateTimeSolved);
        $solvedString = $solved[1]." ";
        $solvedDate = explode("-", $solved[0]);
        $solvedString .= $solvedDate[2]."/".$solvedDate[1];
        $html .= "<h2>".$this->challengeTitle."</h2><br />";
        $html .= "<p>Solved: ".$solvedString."</p>";
        $html .= "<p>Victim: ".$this->targetName."</p>";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }
}

class Config{
	public $startDate;
	public $duration;
	public $motd;
    public $rules;
	public $leftHeader;
	public $centerHeader;
	public $rightHeader;

    const NOTSTARTED = 0;
    const RUNNING = 1;
    const FINISHED = 2;

	public function __construct($startDate, $duration, $motd, $rules, $leftHeader, $centerHeader, $rightHeader){
		$this->startDate = $startDate;
		$this->duration = $duration;
		$this->motd = $motd;
        $this->rules = $rules;
		$this->leftHeader = $leftHeader;
		$this->centerHeader = $centerHeader;
		$this->rightHeader = $rightHeader;
	}

	public function getMOTD(){
		return $this->motd;
	}

    public function getGameState(){
        $now = new DateTime();
        $gameStart = new DateTime($this->startDate);
        $gameEnd = new DateTime($this->startDate);
        $gameEnd->add(new DateInterval('PT'.$this->duration.'H'));
        if($now < $gameStart){
            return Config::NOTSTARTED;
        }
        if($now > $gameEnd){
            return Config::FINISHED;
        }
        return Config::RUNNING;
    }

	public function buildHeaderContent(){
		$header = "";
		$header .= "<span id='header-left'>".explode('<><>', $this->leftHeader)[0]."</span>";
		$header .= "<span id='header-center'>".explode('<><>', $this->centerHeader)[0]."</span>";
		$header .= "<span id='header-right'>".explode('<><>', $this->rightHeader)[0]."</span>";
		return $header;
	}

	public function buildHeaderStyle(){
		$style = "";
		$style .= "#header-left{\r\n\t".explode('<><>', $this->leftHeader)[1]."}\r\n";
		$style .= "#header-center{\r\n\t".explode('<><>', $this->centerHeader)[1]."}\r\n";
		$style .= "#header-right{\r\n\t".explode('<><>', $this->rightHeader)[1]."}\r\n";
		return $style;
	}
}

/**
 * Class BackgroundProcess
 */
class BackgroundProcess{
    private $command;
    private $pid;
    private $outputFile;
    private $lastLine = 0;
    private $filters = array("/^.*\(ECDSA\).*$/", "/^.*Pseudo-terminal.*$/", "/^.*Enter new UNIX password.*$/");
    private $callback;
    private $callbackArgs;

    public function __construct($command, $callback=null, $callbackArgs=array()){
        $this->command = $command;
        $this->callback = $callback;
        $this->callbackArgs = $callbackArgs;
    }

    public function run($outputFile = '/dev/null'){
        error_log($outputFile);
        $this->outputFile = $outputFile;
        if (!file_exists(dirname($outputFile))) {
            mkdir(dirname($outputFile), 0777, true);
        }
        $this->pid = shell_exec(sprintf('%s > %s 2>&1 & echo $!', $this->command, $outputFile ));
        $this->pid = trim($this->pid);
    }

    public function isRunning(){
        try{
            $result = shell_exec(sprintf('ps %d', $this->pid));
            if(count(preg_split("/\n/", $result)) > 2){
                return true;
            }
        }catch (Exception $e){}
        return false;
    }

    public function getPid(){
        return $this->pid;
    }

    public function getOutputFile(){
        return $this->outputFile;
    }

    public function getCallback(){
        return $this->callback;
    }

    public function getCallbackArgs(){
        return $this->callbackArgs;
    }

    public function getNewOutput(){
        if(file_exists($this->outputFile) && strcmp($this->outputFile, "/dev/null") != 0) {
            $fContents = file($this->outputFile);
            $res = array();
            for($this->lastLine; $this->lastLine < count($fContents); $this->lastLine++){
                $match = false;
                foreach($this->filters as $filter){
                    if(preg_match($filter, $fContents[$this->lastLine]) == 1){
                        if(strlen(trim($fContents[$this->lastLine]) > 0)) {
                            $match = true;
                        }
                    }
                }
                if(!$match) {
                    $res[] = $fContents[$this->lastLine];
                }
            }
            return $res;
        }
        return null;
    }
}