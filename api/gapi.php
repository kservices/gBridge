<?php
    require_once('mqtt.php');
    require_once('db.php');    

    //response is always JSON
    header('Content-Type: application/json');

    $request = json_decode(file_get_contents('php://input'), true);

    //check, whether requestId is present
    if(!isset($request['requestId'])){
        errorResponse("", ErrorCode::protocolError, true);
    }

    $handle = db_init();        //prepare database-connection

    //check the auth header
    //exit if the key is invalid
    //@todo notify the user, if possible
    $userid = checkAuth($handle, $request['requestId']);
    
    //See https://developers.google.com/actions/smarthome/create-app for information about the JSON request format
    if(!isset($request['inputs'])){
        errorResponse($request['requestId'], ErrorCode::protocolError, true);
        error_log("Error: Input not specified");
    }
    
    //handle each input
    foreach($request['inputs'] as $input){
        //intent is not defined -> protocol error
        if(!isset($input['intent'])){
            error_log("Intent is undefined!");
            errorResponse($request['requestId'], ErrorCode::protocolError, true);
        }

        if($input['intent'] === 'action.devices.SYNC'){
            //sync-intent
            handleSync($handle, $userid, $request['requestId']);
        }elseif($input['intent'] === 'action.devices.QUERY'){
            //query-intent
            errorResponse($request['requestId'], ErrorCode::unknownError, true);
        }elseif($input['intent'] === 'action.devices.EXECUTE'){
            //execute-intent
            handleExecute($handle, $userid, $request['requestId'], $input);
        }else{
            //unknown intent
            error_log('Unknown intent: "' . $input['intent'] . '"');
            errorResponse($request['requestId'], ErrorCode::protocolError, true);
        }
    }

    /**
     * ----------------------------------------------------------------------------------
     * handling/ helper functions
     * ----------------------------------------------------------------------------------
     */

    /**
     * Check, if the sent Authorization-Header is valid
     * @param handle DB-Handle
     * @param requestid The requestid, necessary for eventually returning an error
     * @return user-id
     */
    function checkAuth($handle, $requestid){
        if(isset(getallheaders()['Authorization'])){
            $authkey = getallheaders()['Authorization'];            //Auth header is in format "Bearer <Auth-Key>"
            $authkey = str_replace('Bearer ', '', $authkey);

            $status = db_getUserByAccessKey($handle, $authkey);

            if($status['error']){
                error_log('Wrong auth-key: ' . $status['msg']);
                errorResponse($requestid, ErrorCode::authExpired, true);
            }

            return $status['user_id'];
        }else{
            error_log('No Authkey specified!');
            errorResponse($requestid, ErrorCode::authFailure, true);   
        }
    }

    /**
     * Handle the Sync-Intent
     * @param handle DB-Handle
     * @param userid The ID of the user that shall be synced.
     * @param requestid The request id
     */
    function handleSync($handle, $userid, $requestid){
        $response = [
            'requestId' => $requestid,
            'payload' => [
                'devices' => [],
                'agentUserId' => $userid            //the agentUserId is here the user_id
            ]
        ];

        $devices = db_getDevicesOfUser($handle, $userid);
        if($devices['error']){
            errorResponse($requestid, ErrorCode::unknownError, true);
        }
        
        foreach($devices['devices'] as $device){
            $trait_googlenames = array_map(function($trait){return $trait['gname'];}, $device['traits']); 
            $response['payload']['devices'][] = [
                'id' => $device['device_id'],
                'type' => $device['gname'],
                'traits' => $trait_googlenames,
                'name' => [
                    'defaultNames' => ['Kappelt Virtual Device'],
                    'name' => $device['name']
                ],
                'willReportState' => false,
                'deviceInfo' => [
                    'manufacturer' => 'Kappelt'
                ]
            ];
        }

        echo json_encode($response);
    }

    /**
     * Handle the Execute-Intent
     * @param handle DB-Handle
     * @param userid The ID of the user that shall be synced.
     * @param requestid The request id
     * @param input the data that shall be handled
     */
    function handleExecute($handle, $userid, $requestid, $input){
        if(!isset($input['payload']['commands'])){
            errorResponse($requestid, ErrorCode::protocolError, true);
        }

        $success = true;
        $handledDeviceIds = [];

        foreach($input['payload']['commands'] as $command){
            $deviceIds = array_map(function($device){return $device['id'];}, $command['devices']);
            $handledDeviceIds = array_merge($handledDeviceIds, $deviceIds);
            foreach($command['execution'] as $exec){
                ob_start();
                var_dump($exec);
                $result = ob_get_clean();
                error_log('EXEC: ' . $result);
                $success &= mqttHandleExecCmd($userid, $deviceIds, $exec);
            }
        }

        $response = [
            'requestId' => $requestid,
            'payload' => [
                'commands' => [
                    [
                        'ids' => array_unique($handledDeviceIds),
                        'status' => ($success ? "SUCCESS":"OFFLINE")
                    ]
                ]
            ]
        ];

        echo json_encode($response);
    }

    //error codes that can be returned
    abstract class ErrorCode{
        const authExpired = "authExpired";
        const authFailure = "authFailure";
        const deviceOffline = "deviceOffline";
        const timeout = "timeout";
        const deviceTurnedOff = "deviceTurnedOff";
        const deviceNotFound = "deviceNotFound";
        const valueOutOfRange = "valueOutOfRange";
        const notSupported = "notSupported";
        const protocolError = "protocolError";
        const unknownError = "unknownError";
    }

    /**
     * Send an error message back
     */
    function errorResponse($requestid, $errorcode, $exit_afterwards = false){
        $error = [
            'requestId' => $requestid,
            'payload' => [
                'errorCode' => $errorcode   
            ]
        ];

        echo json_encode($error);

        if($exit_afterwards){
            exit();
        }
    }
?>