var config = {
    //Connection parameters for the Redis server
    //See https://www.npmjs.com/package/redis for documentation of connection options
    "redis": "redis://192.168.2.174:6379",
    
    //Connection parameters for MQTT server
    //See https://www.npmjs.com/package/mqtt for parameters
    "mqtt": "mqtt://192.168.2.61:1883",

    //generate an API-Key in Google Cloud Dashboard for your project. Make sure that the "Homegraph API" is enabled!
    "homegraph-api-key": "AIzaSyBwEb_RsqZ4jdGoxGljdeQYrWEeygNj2nw"
};

module.exports = config;