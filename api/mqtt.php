<?php
require_once('config.php');
require_once('vendor/autoload.php');

/**
 * Handle an Exec-Command from Google,
 * send the values to the appropriate mqtt topic
 * @retval true Publish was successfull
 * @retval false There was an issue 
 */
function mqttHandleExecCmd($userid, $deviceIds, $execCmd){
    $topicSuffix = "";
    $message = "";
    if($execCmd['command'] === 'action.devices.commands.OnOff'){
        $topicSuffix = 'OnOff';
        $message = $execCmd['params']['on'] ? "1":"0";
    }elseif($execCmd['command'] === 'action.devices.commands.BrightnessAbsolute'){
        $topicSuffix = 'Brightness';
        $message = $execCmd['params']['brightness'];
    }else{
        //unknown execute-command
        return false;
    }

    $success = true;
    foreach($deviceIds as $deviceId){
        $success &= mqttPublishRaw('gBridge/' . "u$userid/d$deviceId/$topicSuffix", $message);
    }

    return $success;
}

/**
 * Publish a message to a specified topic
 * @retval true publish was successfull
 * @retval false There was an issue 
 */
function mqttPublishRaw($topic, $message){
    global $mqtt_broker;
    global $mqtt_port;
    global $mqtt_username;
    global $mqtt_password;

    $mqtt = new Bluerhinos\phpMQTT($mqtt_broker, $mqtt_port, 'assistant-mqtt-bridge-' . uniqid());
    if ($mqtt->connect(true, NULL, $mqtt_username, $mqtt_password)) {
        $mqtt->publish($topic, $message, 0);
        $mqtt->close();
        return true;
    } else {
        return false;
    }
}

?>