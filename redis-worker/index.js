var config = require('./config.js');

/**
 * Redis client
 */
var redislib = require("redis");
//for messages from redis to MQTT broker
var redis_subscribe = redislib.createClient(config.redis);
//for messages from MQTT to be cached with redis
var redis_cache = redislib.createClient(config.redis);

/**
 * MQTT client
 */
var mqttlib = require("mqtt");
var mqtt = mqttlib.connect(config.mqtt);

/**
 * HTTP requests
 */
var axios = require("axios");
const util = require("util");
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

/**
 * Redis-Client functions
 */
redis_subscribe.on('connect', function() {
    console.log('Redis client (subscribe) connected');
});
redis_subscribe.on('error', function (err) {
    console.log('Redis client (subscribe) error: ' + err);
});

redis_cache.on('connect', function() {
    console.log('Redis client (cache) connected');
});
redis_cache.on('error', function (err) {
    console.log('Redis client (cache) error: ' + err);
});

/**
 * Direction from Redis to MQTT broker
 * This way happens, if the user demands an action from Google Assistant (like "turn the lights on")
 * The API function will then publish something on the redis channel, it'll be forwarded to MQTT here.
 */
redis_subscribe.on("psubscribe", function (channel, count) {
    console.log("Redis client (subscribe) successful subscribe to " + channel);
});
redis_subscribe.on("pmessage", function (pattern, channel, message) {
    var channelMatch = /(?:gbridge:u)(\d+)(?::d)(\d+)(?::)(.*)/;

    if(!channelMatch.test(channel)){
        console.log(`Redis client (subscribe) error (test): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    var matches = channelMatch.exec(channel);
    if((matches == null) || (matches.length < 4)){      //4 variables are expected in the match: The whole match itself and the three capture groups
        console.log(`Redis client (subscribe) error (match): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    var userid = matches[1];
    var deviceid = matches[2];
    var devicetrait = matches[3];

    //calling google API to request a sync
    if(devicetrait === 'requestsync'){
        async function requestSync(){
            try {
                const response = await axios.post(`https://homegraph.googleapis.com/v1/devices:requestSync?key=${config["homegraph-api-key"]}`,
                                                    `{agent_user_id: "${userid}"}`,
                                                    {
                                                        headers: {"Content-Type": "application/json"}
                                                    });
            } catch (error) {
                var responseData = "";
                if(error.response){
                    responseData = JSON.stringify(error.response.data);
                }
                console.log("Google Request Sync error: \"" + error + `" for user ${userid}, response ${responseData}`);
            }
          };
        requestSync();
    }

    mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
});
redis_subscribe.psubscribe("gbridge:u*:d*:*");

/**
 * Direction from MQTT broker to Redis
 * This happens, when the user's application sends a status update that will be cached in Redis.
 * If Google demands the current state of a device ("Is the ceiling light turned on?") the state from Redis cache will be used.
 */
mqtt.on('message', function(topic, message){
    message = message.toString();
    var topicMatch = /(?:gBridge\/u)(\d+)(?:\/d)(\d+)(?:\/)(.*)(?:\/set)/;

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
    }else{
        console.log(`MQTT client error: Unsupported trait "${devicetrait}" for user ${userid}`);
        return;
    }

    redis_cache.hset(`gbridge:u${userid}:d${deviceid}`, devicetrait, message);
});
//MQTT topic format is here gBridge/u<userid>/d<deviceid>/<trait>/set
mqtt.subscribe('gBridge/+/+/+/set');