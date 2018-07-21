//You can either set environment variables to configure this module, then just copy config.sample.js to config.js
//Otherwise, copy this file to config.js and change the values there
var config = {
    //Connection parameters for the Redis server
    //See https://www.npmjs.com/package/redis for documentation of connection options
    "redis": process.env.GBRIDGE_REDISWORKER_REDIS || "redis://redis1.int.kappelt.net:6379",
    
    //Connection parameters for MQTT server
    //See https://www.npmjs.com/package/mqtt for parameters and possible options
    "mqtt": process.env.GBRIDGE_REDISWORKER_MQTT || "mqtt://mqtt1.int.kappelt.net:1883",
    "mqttuser": process.env.GBRIDGE_REDISWORKER_MQTTUSER || "mqtt_user",
    "mqttpassword": process.env.GBRIDGE_REDISWORKER_MQTTPASSWORD || "mqtt_password",

    //generate an API-Key in Google Cloud Dashboard for your project. Make sure that the "Homegraph API" is enabled!
    "homegraph-api-key": process.env.GBRIDGE_REDISWORKER_HOMEGRAPHKEY || "your-key"
};

module.exports = config;