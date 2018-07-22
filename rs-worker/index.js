const config = require('./config.js');
const serviceAcc = require(config["service-account-file"]);

const jwt = require('jsonwebtoken');
const axios = require('axios');

const uuid = require('uuid/v4');
const util = require('util');

/**
 * Redis client
 */
const redislib = require("redis");
var redis_subscribe = redislib.createClient(config.redis);
var redis = redislib.createClient(config.redis);

/**
 * MQTT client
 */
var mqttlib = require("mqtt");
var mqtt = mqttlib.connect(config.mqtt, {
    username: config.mqttuser,
    password: config.mqttpassword
});

var accesstoken = "";

function newAccessToken(){
    var claims = {};
    
    //issued at, expiry time and issuer will be handled by jwt lib

    claims["scope"] = "https://www.googleapis.com/auth/homegraph";
    claims["aud"] = "https://accounts.google.com/o/oauth2/token";

    var token = jwt.sign(claims, serviceAcc.private_key, {
        expiresIn: config.accesskey_renew_interval,
        algorithm: 'RS256',
        issuer: serviceAcc.client_email
    });

    axios.post('https://accounts.google.com/o/oauth2/token', 
        'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' + token, {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + token
        }
    }).then(function (response){
        if(!('access_token' in response.data)){
            console.error("[NewAccessToken] Key \"access_token\" was not in response data");
            return;
        }
        accesstoken = response.data.access_token;
        console.log("[NewAccessToken] Successfully generated new access token!");
        //console.log(accesstoken);
    }).catch(function (error){
        console.log("[NewAccessToken] error: " + error);
    });
}

//Periodically create a new access token, after (less than) half of expiry period
newAccessToken();
setInterval(newAccessToken, (config.accesskey_renew_interval - 2) * 500);

/**
 * Report state
 */
function reportState(userid, requestid){
    redis.get(`gbridge:u${userid}:devices`, function(err, response){
        if(err){
            console.error("Redis client error while fetching device for user " + userid +": " + err);
            return;
        }
        if(response == null){
            console.error("Redis client error while fetching devices for user " + userid + ": device list empty");
            return;
        }
       
        response = JSON.parse(response);

        rsData = {
            'agent_user_id': userid,
            'payload': {'devices': {'states': {}}}
        };

        if(requestid != null){
            rsData['requestId'] = requestid;
        }

        Promise.all(Object.keys(response).map(function(deviceid){
            let traits = response[deviceid];

            return new Promise(function(resolve, reject){
                redis.hgetall(`gbridge:u${userid}:d${deviceid}`, function(err, data){
                    if(err){
                        return reject(err);
                    }
                    if(data){
                        rsData['payload']['devices']['states'][deviceid] = {};

                        if(traits.includes('OnOff')){
                            if('onoff' in data){
                                rsData['payload']['devices']['states'][deviceid]['on'] = (data['onoff'] != '0') ? true:false;
                            }else{
                                rsData['payload']['devices']['states'][deviceid]['on'] = false;
                            }
                        }
                        if(traits.includes('Brightness')){
                            if('brightness' in data){
                                rsData['payload']['devices']['states'][deviceid]['brightness'] = parseInt(data['brightness']);
                            }else{
                                rsData['payload']['devices']['states'][deviceid]['brightness'] = 0;
                            }
                        }
                    }
                    resolve();
                });
            });
        })).then(function(){
            //console.log(JSON.stringify(rsData));
            axios.post('https://homegraph.googleapis.com/v1/devices:reportStateAndNotification', 
                rsData, {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + accesstoken,
                    'X-GFE-SSL': 'yes'
                }
            }).then(function (response){
                //console.log('[Report State]  Response: ' + console.log(util.inspect(response, false, null)));
                //Response was OK :)
            }).catch(function (error){
                console.log("[Report State] HTTP error: " + error);
            });
        }).catch(function(err){
            console.log("Error while fetching report-state trait data: " + err);
        });

    });
}

/**
 * Redis client functions
 */
redis_subscribe.on('connect', function() {
    console.log('Redis client (subscribe) connected');
});
redis_subscribe.on('error', function (err) {
    console.log('Redis client (subscribe) error: ' + err);
});
redis.on('connect', function() {
    console.log('Redis client connected');
});
redis.on('error', function (err) {
    console.log('Redis client error: ' + err);
});

redis_subscribe.on("pmessage", function (pattern, channel, message) {
    //only proactively notify on sync
    if(message != 'SYNC'){
        return;
    }

    var channelMatch = /(?:gbridge:u)(\d+)(?::d)(\d+)(?::grequest)/;

    if(!channelMatch.test(channel)){
        console.log(`Redis client (subscribe) error (test): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    var matches = channelMatch.exec(channel);
    if((matches == null) || (matches.length < 3)){      //3 variables are expected in the match: The whole match itself and the two capture groups
        console.log(`Redis client (subscribe) error (match): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    var userid = matches[1];
    redis.hget(`gbridge:u${userid}:d0`, 'grequestid', function(err, response){
        if(err){
            console.error("Redis client error while fetching request id: " + err);
            return;
        }
        if(response == null){
            console.error("Redis client error while fetching request id: undefined request id");
            return;
        }
        reportState(userid, response);
    });

    
});
redis_subscribe.psubscribe("gbridge:u*:d*:grequest");

/**
 * MQTT-Client functions
 */
mqtt.on('connect', function(){
    console.log("MQTT client connected");
});
mqtt.on('error', function(error){
    console.log("MQTT client error: " + error);
});
mqtt.on('offline', function() {
    console.log("MQTT client offline!");
});
mqtt.on('reconnect', function() {
    console.log("MQTT client reconnected!");
});

mqtt.on('message', function(topic, message){
    message = message.toString();
    var topicMatch = /^(?:gBridge\/u)(\d+)(?:\/d)(\d+)(?:\/)([^\/]+)/;

    if(!topicMatch.test(topic)){
        console.log(`MQTT client error (test): Received malformed topic "${topic}" with message "${message}"`);
        return;
    }

    var matches = topicMatch.exec(topic);
    if((matches == null) || (matches.length < 4)){      //4 variables are expected in the match: The whole match itself and the three capture groups
        console.log(`MQTT client error (match): Received malformed topic "${topic}" with message "${message}"`);
        return;
    }

    var userid = matches[1];
    var deviceid = matches[2];
    var devicetrait = matches[3].toLowerCase();

    if(devicetrait === "onoff"){
        if((message == "0") || (message == "false") || (message == "off")){
            message = 0;
        }else{
            message = 1;
        }
    }else if(devicetrait === "brightness"){
        var brightness = Number.parseInt(message);
        if(Number.isNaN(brightness)){
            console.log(`MQTT client error: Wrong brightness "${message}" for user ${userid}`);
            return;
        }
        if(brightness < 0){
            brightness = 0;
        }
        if(brightness > 100){
            brightness = 100;
        }

        message = brightness;
    }else if(devicetrait === "power"){
        //device reporting power state
        if((message == "0") || (message == "false") || (message == "off")){
            message = 0;
        }else{
            message = 1;
        }
    }else{
        console.log(`MQTT client error: Unsupported trait "${devicetrait}" for user ${userid}`);
        return;
    }

    redis.hset(`gbridge:u${userid}:d${deviceid}`, devicetrait, message);

    redis.hget(`gbridge:u${userid}:d0`, 'grequesttype', function(err, response){
        if(err){
            console.error("Redis client error while fetching last request type: " + err);
            return;
        }
        if(response == null){
            console.error("Redis client error while fetching last request type: undefined request type");
            return;
        }

        if(response == 'EXECUTE'){
            //get last requestid if if the previous request was EXECUTE
            redis.hget(`gbridge:u${userid}:d0`, 'grequestid', function(err, response){
                if(err){
                    console.error("Redis client error while fetching request id: " + err);
                    return;
                }
                if(response == null){
                    console.error("Redis client error while fetching request id: undefined request id");
                    return;
                }
                //console.log("Good set");
                reportState(userid, response);
            });
        }else{
            //console.log("Bad set");
            reportState(userid, null);
        }
    });

});
//MQTT topic format is here gBridge/u<userid>/d<deviceid>/<trait>/set
mqtt.subscribe('gBridge/+/+/+/set');
