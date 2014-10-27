<?php
$con = new PDO("mysql:host=".dbhost.";dbname=".dbname.";charset=utf8", dbuser, dbpass);

/**
 * @param $table string Table name to select data from
 * @param $values array An array of column names to select from table. Select * if values is null
 *
 * @return array The results of the query run
 */
function dbInsert($table, $values){
    global $con;
    //Build an insert statement based on $table and $value. This statement can handle inserting any number of values into any table.
    //$values should be an array with column names used as keys and values to insert as values.
    try{
        $sql = "INSERT INTO $table (".implode(', ', array_keys($values)).") VALUES (".implode(', ', array_fill(0, count($values), '?')).")";
        //error_log($sql);
        $stmt = $con->prepare($sql);
        $res = $stmt->execute(array_values($values));
        return $res;
    }catch( Exception $e ){
        error_log("Exception while inserting values into $table.".$e);
    }
    return null;
}

function quote($toQuote){
    global $con;
    return $con->quote($toQuote);
}

/**
 * Selects values from the specified table with specified parameters and returns them as an array of associative arrays
 *
 * @param $table string Table name to select data from
 * @param $values array An array of column names to select from table. Select * if values is null
 * @param $where array An array of key value pairs to be used in the where statement. Keys will be used as column names and values as values ie WHERE key = value
 * @param $inverseWhere bool if true then the values of $where are used as != rather than ==
 *
 * @return array An array of associative arrays  with column names as keys
 */
function dbSelect($table, $values=array(), $where=array(), $inverseWhere=false){
    global $con;
    try{
        $sql = "SELECT ";
        $sql .= (count($values) == 0) ? "*":implode(', ', $values);//Select all if $values is blank
        $sql .= " FROM $table";
        $sql .= (count($where) == 0) ? "":" WHERE ".implode(($inverseWhere ? '<>':'=').'? AND ', array_keys($where)).($inverseWhere ? '<>':'=').'?';
        $con->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        $stmt = $con->prepare($sql);
        if($stmt==false){
            //error_log("SQL SELECT ERROR: ".$con->errorInfo()[2]);
            return null;
        }
        $stmt->execute(array_values($where));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch( Exception $e ){
        error_log("Exception while inserting values into $table.".$e);
    }
    return null;
}

/**
 * Updates values from the specified table with specified parameters. Range of this statement is determined by the value of where (otherwise all rows are updated)
 *
 * @param $table string Table name to select data from
 * @param $values array An Array of column names to select from table. Select * if values is null
 * @param $where array An array of key value pairs to be used in the where statement. Keys will be used as column names and values as values ie WHERE key = value
 *
 * @return boolean true if the update succeeded and false if it failed
 */
function dbUpdate($table, $values, $where=array()){
    global $con;
    try{
        $sql = "UPDATE $table";
        $sql .= (count($values) == 0)? "":" SET ".implode('=?, ', array_keys($values)).'=?';
        $sql .= (count($where) == 0) ? "":" WHERE ".implode('=?,', array_keys($where)).'=?';
        if(strpos($sql, "SET") === false)
            return null;
        $con->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        $stmt = $con->prepare($sql);
        if($stmt==false){
            error_log("SQL UPDATE ERROR: ".$con->errorInfo()[2]);
        }
        return $stmt->execute(array_merge(array_values($values),array_values($where)));
        //return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch( Exception $e ){
        error_log("Exception while updating values in $table.".$e);
    }
    return null;
}

/**
 * Delete a row from the specified table.
 *
 * @param string $table The table name to delete a record from
 * @param array $where Array of key value pairs to limit the range of the delete command. Keys should match column names and values match the matching criteria.
 * @param bool $inverseWhere Inverts the value of where (ie == becomes !=)
 *
 * @return bool True if the delete was successful and false if it failed
 */
function dbDelete($table, $where=array(), $inverseWhere=false){
    global $con;
    try{
        $sql = "DELETE FROM $table";
        $sql .= (count($where) == 0) ? "":" WHERE ".implode(($inverseWhere ? '<>':'=').'?,', array_keys($where)).'=?';
        if(strpos($sql, "WHERE") === false)
            return null;
        $stmt = $con->prepare($sql);
        if($stmt==false){
            error_log("SQL DELETE ERROR: ".$con->errorInfo()[2]);
        }
        return $stmt->execute(array_values($where));
    }catch( Exception $e ){
        error_log("Exception while updating values in $table.".$e);
    }
    return null;
}

//
/**
 * Run a raw SQL statement and return the result.
 * WARNING: There is no validation performed here. It is the callers responsibility to sanitise input data.
 *
 * @param $sql string Raw SQL statement
 * @return array the result set of the supplied query
 */
function dbRaw($sql){
    global $con;
    try{
        $stmt = $con->prepare($sql);
        if($stmt==false){
            //error_log("SQL RAW ERROR: ".$con->errorInfo()[2]);
            return null;
        }
        $stmt->execute();
        if($stmt->errorCode() != 0){
            return $stmt->errorCode();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch( Exception $e ){
        error_log("Exception while running raw statement: $sql.".$e);
    }
    return null;
}

/**
 *Empties the specified table.
 *
 * @param $tableName string The table to truncate
 */
function dbTruncate($tableName){
    global $con;
    $sql = "TRUNCATE TABLE ".$tableName;
    try{
        $stmt = $con->prepare($sql);
        if($stmt==false){
            error_log("SQL RAW ERROR: ".$con->errorInfo()[2]);
            error_log($sql);
        }
        $stmt->execute();
        if($stmt->errorCode() != 0){
            return $stmt->errorCode();
        }
    }catch( Exception $e ){
        error_log("Exception while running raw statement: $sql.".$e);
    }
}

function getConfig(){
    $config = dbSelect("config", array(), array(), false)[0];
    return new Config($config['startTime'],$config['duration'],$config['motd'],$config['rules'], $config['leftHeader'],$config['centerHeader'],$config['rightHeader']);
}

function getTeamList(){
    //Define an empty array. This will be filled with user info
    $returnArray = array();
    //Select all users from the teams table
    $teams = dbSelect("teams", array(), array(), false);
    foreach ($teams as $team){
        //Check if the current user is an admin then create a new key value pair in the user array
        $adminCheck = dbSelect("admins",array(),array('id'=>$team['id']), false);
        $team['isAdmin'] = (!empty($adminCheck)) ? true:false;
        //Add the user array to the array we are returning
        $returnArray[] = $team;
    }
    return $returnArray;
}

function getChallengeList(){
    $returnArray = array();
    $challenges = dbSelect("challenge_templates", array(), array(), false);
    foreach ($challenges as $challenge){
        //Build a new challenge object for each challenge
        $returnArray[] = new Challenge($challenge['id'],$challenge['name'],base64_encode($challenge['image']),$challenge['author'],$challenge['enabled'], $challenge['value']);
    }
    return $returnArray;
}

function getChallengesForCurrentTeam($teamId){
    $returnArray = array();
    $challenges = dbRaw("SELECT t.name, t.image, t.enabled, i.id, t.author, t.value, i.username, i.password FROM challenge_templates AS t INNER JOIN challenge_instance AS i ON t.id=i.parentId WHERE i.teamId = ".$teamId." AND t.enabled=1");

    foreach ($challenges as $challenge){
        //Build a new challenge object for each challenge
        $returnArray[] = new Challenge($challenge['id'],$challenge['name'],base64_encode($challenge['image']),$challenge['author'],$challenge['enabled'], $challenge['value'],$challenge['username'],$challenge['password'], $teamId);
    }
    return $returnArray;
}

function getTeamType(){
    $team = dbSelect("teams", array(), array('id'=>$_SESSION['teamid']), false)[0];
    return $team['type'];
}


function getAchievementsForTeam(){
    $returnArray = array();
    //Get all flags captured by this team
    $achievements = dbRaw("SELECT ct.name AS challengeName, a.timeSubmitted AS time, t.teamName AS victim, ct.image, ct.value AS points FROM attempts AS a INNER JOIN flag AS f ON f.flag = a.flagSubmitted INNER JOIN teams AS te ON te.id = a.teamid INNER JOIN challenge_instance AS c ON c.flagId = f.id INNER JOIN teams AS t ON c.teamId = t.id INNER JOIN challenge_templates AS ct ON c.parentId = ct.id WHERE a.correct=1 AND te.id=".$_SESSION['teamid']);
    foreach ($achievements as $achievement) {
        $returnArray[] = new Achievement($achievement['challengeName'], $achievement['time'], $achievement['victim'], base64_encode($achievement['image']), $achievement['points']);
    }
    return $returnArray;
}
