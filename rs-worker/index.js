const config = require("./config.js");
const serviceAcc = require(config["service-account-file"]);

const jwt = require("jsonwebtoken");
const axios = require("axios");

const uuid = require("uuid/v4");
const util = require("util");

/**
 * Redis client
 */
const redislib = require("async-redis");
var redis_subscribe = redislib.createClient(config.redis);
var redis = redislib.createClient(config.redis);

/**
 * MQTT client
 */
var mqttlib = require("mqtt");
var mqtt = mqttlib.connect(
  config.mqtt,
  {
    username: config.mqttuser,
    password: config.mqttpassword
  }
);

var accesstoken = "";

function newAccessToken() {
  var claims = {};

  //issued at, expiry time and issuer will be handled by jwt lib

  claims["scope"] = "https://www.googleapis.com/auth/homegraph";
  claims["aud"] = "https://accounts.google.com/o/oauth2/token";

  var token = jwt.sign(claims, serviceAcc.private_key, {
    expiresIn: config.accesskey_renew_interval,
    algorithm: "RS256",
    issuer: serviceAcc.client_email
  });

  axios
    .post(
      "https://accounts.google.com/o/oauth2/token",
      "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=" +
      token,
      {
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Authorization: "Bearer " + token
        }
      }
    )
    .then(function (response) {
      if (!("access_token" in response.data)) {
        console.error(
          '[NewAccessToken] Key "access_token" was not in response data'
        );
        return;
      }
      accesstoken = response.data.access_token;
      console.log("[NewAccessToken] Successfully generated new access token!");
      //console.log(accesstoken);
    })
    .catch(function (error) {
      console.log("[NewAccessToken] error: " + error);
    });
}

//Periodically create a new access token, after (less than) half of expiry period
newAccessToken();
setInterval(newAccessToken, (config.accesskey_renew_interval - 2) * 500);

/**
 * Report state
 */
async function reportState(userid, requestid) {
  var response;
  try {
    response = await redis.get(`gbridge:u${userid}:devices`);
  } catch (e) {
    console.error(
      "Redis client error while fetching device for user " + userid + ": " + err
    );
    return;
  }

  if (response == null) {
    console.error(
      "Redis client error while fetching devices for user " +
      userid +
      ": device list empty"
    );
    return;
  }

  response = JSON.parse(response);

  var rsData = {
    agent_user_id: userid,
    payload: { devices: { states: {} } }
  };

  if (requestid != null) {
    rsData["requestId"] = requestid;
  }

  var deviceids = Object.keys(response);
  for (var i = 0; i < deviceids.length; i++) {
    var deviceid = deviceids[i];
    var traits = response[deviceid];
    var data;
    rsData["payload"]["devices"]["states"][deviceid] = {};

    try {
      data = await redis.hgetall(`gbridge:u${userid}:d${deviceid}`);
      if (data == null) {
        throw "Returned data is null";
      }
    } catch (e) {
      //default values if not yet set, do not throw error
      if ("OnOff" in traits) {
        rsData["payload"]["devices"]["states"][deviceid]["on"] = false;
      }
      if ("Brightness" in traits) {
        rsData["payload"]["devices"]["states"][deviceid]["brightness"] = 0;
      }
      if ("TempSet.Mode" in traits) {
        rsData["payload"]["devices"]["states"][deviceid]["thermostatMode"] =
          "off";
      }
      if ("TempSet.Setpoint" in traits) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureSetpoint"
        ] = 0.0;
      }
      if ("TempSet.Ambient" in traits) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureAmbient"
        ] = 0.0;
      }
      if ("TempSet.Humidity" in traits) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatHumidityAmbient"
        ] = 0.0;
      }
      if ("FanSpeed" in traits) {
        rsData["payload"]["devices"]["states"][deviceid][
          "currentFanSpeedSetting"
        ] = "S1";
      }
      if ("StartStop" in traits) {
        rsData["payload"]["devices"]["states"][deviceid]["isRunning"] = false;
        rsData["payload"]["devices"]["states"][deviceid]["isPaused"] = false;
      }
      if ("OpenClose" in traits) {
        rsData["payload"]["devices"]["states"][deviceid]["openPercent"] = 0;
      }
      //"Scene"-Trait and "CameraStream" do not need report-state

      //Check if no states are defined for this device, then completely remove it from the record
      //This happens if this device has no states that need to be reported (e.g. Scenes)
      if (
        !Object.keys(rsData["payload"]["devices"]["states"][deviceid]).length
      ) {
        delete rsData["payload"]["devices"]["states"][deviceid];
      }
      continue;
    }

    if ("OnOff" in traits) {
      if ("onoff" in data) {
        rsData["payload"]["devices"]["states"][deviceid]["on"] =
          data["onoff"] != "0" ? true : false;
      } else {
        rsData["payload"]["devices"]["states"][deviceid]["on"] = false;
      }
    }
    if ("Brightness" in traits) {
      if ("brightness" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "brightness"
        ] = parseInt(data["brightness"]);
      } else {
        rsData["payload"]["devices"]["states"][deviceid]["brightness"] = 0;
      }
    }
    if ("TempSet.Mode" in traits) {
      if ("tempset.mode" in data) {
        rsData["payload"]["devices"]["states"][deviceid]["thermostatMode"] =
          data["tempset.mode"];
      } else {
        rsData["payload"]["devices"]["states"][deviceid]["thermostatMode"] =
          "off";
      }
    }
    if ("TempSet.Setpoint" in traits) {
      if ("tempset.setpoint" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureSetpoint"
        ] = Number.parseFloat(data["tempset.setpoint"]);
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureSetpoint"
        ] = 0.0;
      }
    }
    if ("TempSet.Ambient" in traits) {
      if ("tempset.ambient" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureAmbient"
        ] = Number.parseFloat(data["tempset.ambient"]);
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatTemperatureAmbient"
        ] = 0.0;
      }
    }
    if ("TempSet.Humidity" in traits) {
      if ("tempset.humidity" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatHumidityAmbient"
        ] = Number.parseFloat(data["tempset.humidity"]);
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "thermostatHumidityAmbient"
        ] = 0.0;
      }
    }
    if ("FanSpeed" in traits) {
      if ("fanspeed" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "currentFanSpeedSetting"
        ] = data["fanspeed"];
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "currentFanSpeedSetting"
        ] = "S1";
      }
    }
    if ("StartStop" in traits) {
      if ("startstop" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "isRunning"
        ] = (data["startstop"] != "0") ? true : false;
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "isRunning"
        ] = "false";
      }
    }
    if ("OpenClose" in traits) {
      if ("openclose" in data) {
        rsData["payload"]["devices"]["states"][deviceid][
          "openPercent"
        ] = parseInt(data["openclose"]);
      } else {
        rsData["payload"]["devices"]["states"][deviceid][
          "openPercent"
        ] = 0;
      }
    }
    //"Scene"-Trait and "CameraStream" does not need report-state

    if ("power" in data) {
      rsData["payload"]["devices"]["states"][deviceid]["online"] =
        data["power"] != "0" ? true : false;
    }

    //Check if no states are defined for this device, then completely remove it from the record
    //This happens if this device has no states that need to be reported (e.g. Scenes)
    if (!Object.keys(rsData["payload"]["devices"]["states"][deviceid]).length) {
      delete rsData["payload"]["devices"]["states"][deviceid];
    }
  }

  //console.log(JSON.stringify(rsData));
  axios
    .post(
      "https://homegraph.googleapis.com/v1/devices:reportStateAndNotification",
      rsData,
      {
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + accesstoken,
          "X-GFE-SSL": "yes"
        }
      }
    )
    .then(function (response) {
      //console.log('[Report State]  Response: ' + console.log(util.inspect(response, false, null)));
      //Response was OK :)
    })
    .catch(function (error) {
      console.log(`[Report State] HTTP error (user ${userid}): ` + error);
    });
}

/**
 * Redis client functions
 */
redis_subscribe.on("connect", function () {
  console.log("Redis client (subscribe) connected");
});
redis_subscribe.on("error", function (err) {
  console.log("Redis client (subscribe) error: " + err);
});
redis.on("connect", function () {
  console.log("Redis client connected");
});
redis.on("error", function (err) {
  console.log("Redis client error: " + err);
});

redis_subscribe.on("pmessage", async function (pattern, channel, message) {
  //only proactively notify on sync
  if (message != "SYNC") {
    return;
  }

  var channelMatch = /(?:gbridge:u)(\d+)(?::d)(\d+)(?::grequest)/;

  if (!channelMatch.test(channel)) {
    console.log(
      `Redis client (subscribe) error (test): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`
    );
    return;
  }

  var matches = channelMatch.exec(channel);
  if (matches == null || matches.length < 3) {
    //3 variables are expected in the match: The whole match itself and the two capture groups
    console.log(
      `Redis client (subscribe) error (match): Received data on malformed channel "${channel}" for pattern "${pattern}" with data "${message}"`
    );
    return;
  }

  var userid = matches[1];
  var response;
  try {
    response = await redis.hget(`gbridge:u${userid}:d0`, "grequestid");
  } catch (e) {
    console.error("Redis client error while fetching request id: " + err);
    return;
  }
  if (response == null) {
    console.error(
      "Redis client error while fetching request id: undefined request id"
    );
    return;
  }
  await reportState(userid, response);
});
redis_subscribe.psubscribe("gbridge:u*:d*:grequest");

/**
 * MQTT-Client functions
 */
mqtt.on("connect", function () {
  console.log("MQTT client connected");
});
mqtt.on("error", function (error) {
  console.log("MQTT client error: " + error);
});
mqtt.on("offline", function () {
  console.log("MQTT client offline!");
});
mqtt.on("reconnect", function () {
  console.log("MQTT client reconnected!");
});

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
    "user-id": null,
    "user-custom-part": null,
    "error-message": "Not yet initialized"
  };

  var topicMatch = /^(?:gBridge\/u)([0-9]+)(?:\/)(.*)/;

  if (!topicMatch.test(topic)) {
    //The regex doesn't match
    returnval["error-message"] = "Regex not matching!";
    return returnval;
  }

  var matches = topicMatch.exec(topic);
  if (matches == null || matches.lenth < 3) {
    //Three groups are expected: the whole match and three capture groups
    returnval["error-message"] = "Regex result either null or too short";
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
 * It returns an associative error containing the information or throws an error
 */
async function getDevicesOfUser(userid) {
  var response;
  try {
    response = await redis.get(`gbridge:u${userid}:devices`);
  } catch (e) {
    console.error(
      "Redis client error while fetching device for user " + userid + ": " + e
    );
    throw e;
  }

  if (response == null) {
    throw "Redis client error while fetching devices for user " +
    userid +
    ": device list empty";
    return;
  }

  var info;

  try {
    info = JSON.parse(response);
  } catch (e) {
    console.error("JSON parsing error for user " + userid + ": " + e, null);
    throw e;
  }

  return info;
}

mqtt.on("message", async function (topic, message) {
  message = message.toString();

  topicinfo = guessUserIdFromMqttTopic(topic);
  if (topicinfo["error-message"] != null) {
    console.error(
      `Could not determine user in topic "${topic}": ${
      topicinfo["error-message"]
      }`
    );
    return;
  }

  let userid = topicinfo["user-id"];
  let topicuserpart = topicinfo["user-custom-part"];

  //Filter topic that were published by the script itself, just quietly return
  if (topicuserpart === "d0/grequest" || topicuserpart === "d0/requestsync") {
    return;
  }

  var devices;
  try {
    devices = await getDevicesOfUser(userid);
  } catch (e) {
    console.error("Error in mapping status topic: " + e);
    return;
  }

  let deviceid = null;
  let devicetrait = null;

  //look for the device/ trait that matches the user part of the topic
  for (var currentDeviceId in devices) {
    for (var currentTrait in devices[currentDeviceId]) {
      //This script has published to the topic, since it is an action topic. Just quietly return
      if (
        devices[currentDeviceId][currentTrait]["actionTopic"] === topicuserpart
      ) {
        return;
      }

      //The topic was the actual status topic
      if (
        devices[currentDeviceId][currentTrait]["statusTopic"] === topicuserpart
      ) {
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
      if (matches != null && matches.length == 2) {
        //It is a standard "Power-Topic" that can't be changed by the user
        deviceid = matches[1];
        devicetrait = "power";
      }
    }
  }

  //Topic could still not be matched
  if (!deviceid) {
    console.error(`Could not match topic ${topicuserpart} for user ${userid}`);
    return;
  }

  if (devicetrait === "onoff") {
    message = String(message).toLowerCase();
    if (message == "0" || message == "false" || message == "off") {
      message = 0;
    } else {
      message = 1;
    }
  } else if (devicetrait === "brightness") {
    var brightness = Number.parseInt(message);
    if (Number.isNaN(brightness)) {
      console.log(
        `MQTT client error: Wrong brightness "${message}" for user ${userid}`
      );
      return;
    }
    if (brightness < 0) {
      brightness = 0;
    }
    if (brightness > 100) {
      brightness = 100;
    }

    message = brightness;
  } else if (devicetrait === "scene") {
    //no special handling for scenes required
    message = 1;
  } else if (devicetrait === "tempset.mode") {
    var requestedMode = String(message)
      .toLowerCase()
      .trim();
    var allModes = [
      "off",
      "heat",
      "cool",
      "on",
      "auto",
      "fan-only",
      "purifier",
      "eco",
      "dry"
    ];

    if (allModes.indexOf(requestedMode) < 0) {
      console.log(
        `MQTT client error: Wrong thermostat mode "${message}" for user ${userid}`
      );
      return;
    }

    message = requestedMode;
  } else if (devicetrait === "tempset.setpoint") {
    var temperature = Number.parseFloat(message);
    if (Number.isNaN(temperature)) {
      console.log(
        `MQTT client error: Wrong temperature (set) "${message}" for user ${userid}`
      );
      return;
    }

    message = temperature;
  } else if (devicetrait === "tempset.ambient") {
    var temperature = Number.parseFloat(message);
    if (Number.isNaN(temperature)) {
      console.log(
        `MQTT client error: Wrong temperature (amb) "${message}" for user ${userid}`
      );
      return;
    }

    message = temperature;
  } else if (devicetrait === "tempset.humidity") {
    var humidity = Number.parseFloat(message);
    if (Number.isNaN(humidity)) {
      console.log(
        `MQTT client error: Wrong humidity "${message}" for user ${userid}`
      );
      return;
    }

    if (humidity < 0.0) {
      humidity = 0.0;
    }
    if (humidity > 100.0) {
      humidity = 100.0;
    }

    message = humidity;
  } else if (devicetrait === "fanspeed") {
    message = String(message).trim();
  } else if (devicetrait === "startstop") {
    message = String(message).toLowerCase();
    if ((message == "0") || (message == "false") || (message == "stop")) {
      message = 0;
    } else {
      message = 1;
    }
  } else if (devicetrait === "openclose") {
    var open = Number.parseInt(message);
    if (Number.isNaN(open)) {
      console.log(
        `MQTT client error: Wrong openclose "${message}" for user ${userid}`
      );
      return;
    }
    if (open < 0) {
      open = 0;
    }
    if (open > 100) {
      open = 100;
    }

    message = open;
  } else if(devicetrait === 'camerastream'){
    message = String(message);
  } else if (devicetrait === "power") {
    //device reporting power state
    message = String(message).toLowerCase();
    if (message == "0" || message == "false" || message == "off") {
      message = 0;
    } else {
      message = 1;
    }
  } else {
    console.log(
      `MQTT client error: Unsupported trait "${devicetrait}" for user ${userid}`
    );
    return;
  }

  await redis.hset(`gbridge:u${userid}:d${deviceid}`, devicetrait, message);

  var requesttype;
  try {
    requesttype = await redis.hget(`gbridge:u${userid}:d0`, "grequesttype");
  } catch (e) {
    console.error("Redis client error while fetching last request type: " + e);
    return;
  }

  if (requesttype == null) {
    console.error(
      "Redis client error while fetching last request type: undefined request type"
    );
    return;
  }

  if (requesttype == "EXECUTE") {
    //get last requestid if if the previous request was EXECUTE
    var grequestid;
    try {
      grequestid = await redis.hget(`gbridge:u${userid}:d0`, "grequestid");
    } catch (e) {
      console.error("Redis client error while fetching request id: " + e);
      return;
    }
    await reportState(userid, grequestid);
  } else {
    await reportState(userid, null);
  }
});
//Subscribe to all topic that may fit
mqtt.subscribe("gBridge/+/#");
