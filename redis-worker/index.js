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
var mqtt = mqttlib.connect(config.mqtt, {
    username: config.mqttuser,
    password: config.mqttpassword
});

/**
 * HTTP requests
 */
var axios = require("axios");
const util = require("util");
/**
 * MQTT-Client functions
 */
mqtt.on('connect', function () {
    console.log("MQTT client connected");
});
mqtt.on('error', function (error) {
    console.log("MQTT client error: " + error);
});
mqtt.on('offline', function () {
    console.log("MQTT client offline!");
});
mqtt.on('reconnect', function () {
    console.log("MQTT client reconnected!");
});

/**
 * Redis-Client functions
 */
redis_subscribe.on('connect', function () {
    console.log('Redis client (subscribe) connected');
});
redis_subscribe.on('error', function (err) {
    console.log('Redis client (subscribe) error: ' + err);
});

redis_cache.on('connect', function () {
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

    if (!channelMatch.test(channel)) {
        console.log(`Redis client (subscribe) error (test): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    var matches = channelMatch.exec(channel);
    if ((matches == null) || (matches.length < 4)) {      //4 variables are expected in the match: The whole match itself and the three capture groups
        console.log(`Redis client (subscribe) error (match): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`);
        return;
    }

    let userid = matches[1];
    let deviceid = matches[2];
    let devicetrait = matches[3];

    //calling google API to request a sync
    if (devicetrait === 'requestsync') {
        async function requestSync() {
            try {
                const response = await axios.post(`https://homegraph.googleapis.com/v1/devices:requestSync?key=${config["homegraph-api-key"]}`,
                    `{agent_user_id: "${userid}"}`,
                    {
                        headers: { "Content-Type": "application/json" }
                    });
            } catch (error) {
                var responseData = "";
                if (error.response) {
                    responseData = JSON.stringify(error.response.data);
                }
                console.log("Google Request Sync error: \"" + error + `" for user ${userid}, response ${responseData}`);
            }
        };
        requestSync();
    }

    getDevicesOfUser(userid, function (err, info) {
        if (err) {
            console.error('Error on action topic handling: ' + err);
            return;
        }

        //use default MQTT topic except for some user defined ones
        if (!(`${deviceid}` in info)) {
            mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            return;
        }

        deviceinfo = info[deviceid];

        if (devicetrait === 'onoff') {
            if ('OnOff' in deviceinfo) {
                mqtt.publish(`gBridge/u${userid}/${deviceinfo['OnOff']['actionTopic']}`, message);
            } else {
                mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            }
        } else if (devicetrait === 'brightness') {
            if ('Brightness' in deviceinfo) {
                mqtt.publish(`gBridge/u${userid}/${deviceinfo['Brightness']['actionTopic']}`, message);
            } else {
                mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            }
        } else if (devicetrait === 'scene') {
            if ('Scene' in deviceinfo) {
                mqtt.publish(`gBridge/u${userid}/${deviceinfo['Scene']['actionTopic']}`, message);
            } else {
                mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            }
        } else if (devicetrait === 'tempset.mode') {
            if ('TempSet.Mode' in deviceinfo) {
                mqtt.publish(`gBridge/u${userid}/${deviceinfo['TempSet.Mode']['actionTopic']}`, message);
            } else {
                mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            }
        } else if (devicetrait === 'tempset.setpoint') {
            if ('TempSet.Mode' in deviceinfo) {
                mqtt.publish(`gBridge/u${userid}/${deviceinfo['TempSet.Setpoint']['actionTopic']}`, message);
            } else {
                mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
            }
        } else {
            mqtt.publish(`gBridge/u${userid}/d${deviceid}/${devicetrait}`, message);
        }
    });

});
redis_subscribe.psubscribe("gbridge:u*:d*:*");

/**
 * Users write to MQTT topics, formatted "gBridge/u<user-id>/<user-custom-part>"
 * This function tries to isolate the user id from the topic
 * It returns the following array:
 * {
 *  user-id: <user-id>, null if it couldn't be detected
 *  user-custom-part: the user's custom part of the topic, null if it couldn't be determined
 *  error-message: a human readable message for logging, null if everything seems to be ok
 * }
 * @param {*} topic the MQTT topic
 */
function guessUserIdFromMqttTopic(topic) {
    var returnval = {
        'user-id': null,
        'user-custom-part': null,
        'error-message': 'Not yet initialized'
    };

    var topicMatch = /^(?:gBridge\/u)([0-9]+)(?:\/)(.*)/;

    if (!topicMatch.test(topic)) {
        //The regex doesn't match
        returnval["error-message"] = 'Regex not matching!';
        return returnval;
    }

    var matches = topicMatch.exec(topic);
    if ((matches == null) || (matches.lenth < 3)) {        //Three groups are expected: the whole match and three capture groups
        returnval["error-message"] = 'Regex result either null or too short';
        return returnval;
    }

    //Everything is fine...
    returnval["user-id"] = matches[1];
    returnval["user-custom-part"] = matches[2];
    returnval["error-message"] = null;

    return returnval;
}

/**
 * Get info about a user's devices, including the traits and the topics.
 * The callback takes to params:
 *  - err: Human readable error message if there's a problem, null if not
 *  - data: An associative array containing the information
 */
function getDevicesOfUser(userid, callback) {
    redis_cache.get(`gbridge:u${userid}:devices`, function (err, response) {
        if (err) {
            callback("Redis client error while fetching device for user " + userid + ": " + err, null);
            return;
        }
        if (response == null) {
            callback("Redis client error while fetching devices for user " + userid + ": device list empty", null);
            return;
        }

        try {
            info = JSON.parse(response);
        } catch (e) {
            callback('JSON parsing error for user ' + userid + ': ' + e, null);
            return;
        }

        callback(null, info);
    });
}

/**
 * Direction from MQTT broker to Redis
 * This happens, when the user's application sends a status update that will be cached in Redis.
 * If Google demands the current state of a device ("Is the ceiling light turned on?") the state from Redis cache will be used.
 */
mqtt.on('message', function (topic, message) {
    message = message.toString();

    topicinfo = guessUserIdFromMqttTopic(topic);
    if (topicinfo['error-message'] != null) {
        console.error(`Could not determine user in topic "${topic}": ${topicinfo['error-message']}`);
        return;
    }

    let userid = topicinfo['user-id'];
    let topicuserpart = topicinfo['user-custom-part'];

    //Filter topic that were published by the script itself, just quietly return
    if ((topicuserpart === 'd0/grequest') || (topicuserpart === 'd0/requestsync')) {
        return;
    }

    getDevicesOfUser(userid, function (err, devices) {
        //Check for error, abort if necessary
        if (err) {
            console.error('Error in mapping status topic: ' + err);
            return;
        }

        let deviceid = null;
        let devicetrait = null;

        //look for the device/ trait that matches the user part of the topic
        for (var currentDeviceId in devices) {
            for (var currentTrait in devices[currentDeviceId]) {
                //This script has published to the topic, since it is an action topic. Just quietly return
                if (devices[currentDeviceId][currentTrait]['actionTopic'] === topicuserpart) {
                    return;
                }

                //The topic was the actual status topic
                if (devices[currentDeviceId][currentTrait]['statusTopic'] === topicuserpart) {
                    deviceid = currentDeviceId;
                    devicetrait = currentTrait.toLowerCase();
                }
            }
        }

        //The topic wasn't found for the user
        //special case: Power-Topics, are "gBrige/u<user-id>/d<device-id>/power"
        if (!deviceid) {
            var powerMatch = /^(?:d)([0-9]+)(?:\/power)/;
            if (powerMatch.test(topicuserpart)) {
                var matches = powerMatch.exec(topicuserpart);
                if ((matches != null) && (matches.length == 2)) {
                    //It is a standard "Power-Topic" that can't be changed by the user
                    deviceid = matches[1];
                    devicetrait = 'power';
                }
            }
        }

        //Topic could still not be matched
        if (!deviceid) {
            console.error(`Could not match topic ${topicuserpart} for user ${userid}`);
            return;
        }

        if (devicetrait === "onoff") {
            message = String(message).toLowerCase().trim();
            if ((message == "0") || (message == "false") || (message == "off")) {
                message = 0;
            } else {
                message = 1;
            }
        } else if (devicetrait === "brightness") {
            var brightness = Number.parseInt(message);
            if (Number.isNaN(brightness)) {
                console.log(`MQTT client error: Wrong brightness "${message}" for user ${userid}`);
                return;
            }
            if (brightness < 0) {
                brightness = 0;
            }
            if (brightness > 100) {
                brightness = 100;
            }

            message = brightness;
        } else if (devicetrait === "tempset.mode") {
            var requestedMode = String(message).toLowerCase().trim();
            var allModes = ['off', 'heat', 'cool', 'on', 'auto', 'fan-only', 'purifier', 'eco', 'dry'];

            if (allModes.indexOf(requestedMode) < 0) {
                console.log(`MQTT client error: Wrong thermostat mode "${message}" for user ${userid}`);
                return;
            }

            message = requestedMode;
        } else if (devicetrait === "tempset.setpoint") {
            var temperature = Number.parseFloat(message);
            if (Number.isNaN(temperature)) {
                console.log(`MQTT client error: Wrong temperature (set) "${message}" for user ${userid}`);
                return;
            }

            message = temperature;
        } else if (devicetrait === "tempset.ambient") {
            var temperature = Number.parseFloat(message);
            if (Number.isNaN(temperature)) {
                console.log(`MQTT client error: Wrong temperature (amb) "${message}" for user ${userid}`);
                return;
            }

            message = temperature;
        } else if (devicetrait === "tempset.humidity") {
            var humidity = Number.parseFloat(message);
            if (Number.isNaN(humidity)) {
                console.log(`MQTT client error: Wrong humidity "${message}" for user ${userid}`);
                return;
            }

            if(humidity < 0.0){
                humidity = 0.0;
            }
            if(humidity > 100.0){
                humidity = 100.0;
            }

            message = humidity;
        } else if (devicetrait === "scene") {
            //no special handling for scenes required
            message = 1;
        } else if (devicetrait === "power") {
            //device reporting power state
            message = String(message).toLowerCase();
            if ((message == "0") || (message == "false") || (message == "off")) {
                message = 0;
            } else {
                message = 1;
            }
        } else {
            console.log(`MQTT client error: Unsupported trait "${devicetrait}" for user ${userid}`);
            return;
        }

        redis_cache.hset(`gbridge:u${userid}:d${deviceid}`, devicetrait, message);
    });
});
//Subscribe to all topic that may fit
mqtt.subscribe('gBridge/+/#');