<?php
/*
 * This script manages the real time messaging service. Messages will be read from the message queue and send to all connected clients.
 * Messages are not buffered so if a client is not available when the message is sent then they will miss the message
 */

require('include/config.inc.php');

header("Content-Type: text/event-stream\n\n");

function pushStatusUpdate($type, $message, $messageId=-1){
    $data = array();
    $data['type'] = $type;
    $data['id'] = $messageId;
    $data['message'] = $message;
    echo "event: message\n";
    pushMessage(json_encode($data));
}

function pushMessage($message){
    echo "data: ".$message." \n\n";
    ob_flush();
    flush();
}

//Check to see if this user has any background processes running
if(isset($_SESSION['BGProcess'])){
    //Background process found
    $bg = unserialize($_SESSION['BGProcess']);
    if($bg != null){
        //Get a list of new messages to display
        $messages = $bg->getNewOutput();
        //Sent all new messages as a notification
        foreach($messages as $message){
            pushStatusUpdate("message", $message);
        }

        //If the background process is finished then unset the session
        if(!$bg->isRunning()){
            unset($_SESSION['BGProcess']);
            //Check to see if this background process had a callback function
            $callback = $bg->getCallback();
            $callbackArgs = $bg->getCallbackArgs();
            if($callback != null){
                //Found a callback function, so call it
                pushStatusUpdate("message", call_user_func_array($callback, $callbackArgs));
            }
        }else {
            //Store the new version of the class in the session variable
            $_SESSION['BGProcess'] = serialize($bg);
        }
    }else{
        unset($_SESSION['BGProcess']);
    }
}


$sql = "SELECT * FROM rtmsMessageQueue WHERE timeAdded > ".(time()-30);
$toSend = dbRaw($sql);//Get all messages from the last 30 seconds

if(count($toSend) == 0){
    exit();
}

foreach($toSend as $message){
    pushStatusUpdate($message['type'], $message['message'], $message['id']);
    $lastSentId = $message['id'];
}

