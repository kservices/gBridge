var config = {
    //Connection parameters for the Redis server
    //See https://www.npmjs.com/package/redis for documentation of connection options
    "redis": "redis://redis1.int.kappelt.net:6379",
    
    //Connection parameters for MQTT server
    //See https://www.npmjs.com/package/mqtt for parameters
    "mqtt": "mqtt://mqtt1.int.kappelt.net:1883"
};

module.exports = config;