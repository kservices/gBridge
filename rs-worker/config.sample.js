//You can either set environment variables to configure this module, then just copy config.sample.js to config.js
//Otherwise, copy this file to config.js and change the values there
var config = {
    //Connection parameters for the Redis server
    //Connection parameters for the Redis server
    //See https://www.npmjs.com/package/redis for documentation of connection options
    "redis": process.env.GBRIDGE_RSWORKER_REDIS || "redis://redis1.int.kappelt.net:6379",
    
    //Connection parameters for MQTT server
    //See https://www.npmjs.com/package/mqtt for parameters and possible options
    "mqtt": process.env.GBRIDGE_RSWORKER_MQTT || "mqtt://mqtt1.int.kappelt.net:1883",
    "mqttuser": process.env.GBRIDGE_RSWORKER_MQTTUSER || "mqtt_user",
    "mqttpassword": process.env.GBRIDGE_RSWORKER_MQTTPASSWORD || "mqtt_password",

    /**
     * JSON info file with private key for an service account you've created in the cloud dashboard.
     * See: https://developers.google.com/actions/smarthome/report-state
     */
    "service-account-file": process.env.GBRIDGE_RSWORKER_SERVICEACCOUNTFILE || "./kappelt-gbridge-dev-xyz.json",

    /**
     * The acess key for homegraph access must be refreshed periodically
     * set the intervall in seconds
     */
    "accesskey_renew_interval": process.env.GBRIDGE_RSWORKER_ACCESSKEY_RENEW_INTERVAL || 60*60
};

module.exports = config;