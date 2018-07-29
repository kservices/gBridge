FHEM examples
=======================

This page will be filled with a couple of examples that explain the integration of gBridge in FHEM.

MQTT server connection
--------------------------

.. WARNING::
    Sadly, MQTT over TLS does not yet work in FHEM. This example shows a direct, insecure connection to the gBridge MQTT server **that should never be done on a production system**. 
    The only way to currently do it properly is the usage of :ref:`a local Mosquitto instance as a bridge/ proxy <mqttServer-mosquittoBridge>`. If you've setup your local broker to act as a bridge, you can just specify it here for the MQTT connection.

.. code-block:: text

   define Connections.gBridge MQTT mqtt.gbridge.kappelt.net:1883 {gbridge-mqtt-username} {gbridge-mqtt-password}

Basic On/Off and Brightness
-------------------------------

This example shows the configuration to control a device with the following feature set:

* :code:`set {devicename} on` turns the device on
* :code:`set {devicename} off` turns the device off
* :code:`set {devicename} pct {value}` sets the brightness of the device, where value is inbetween 0-100

.. code-block:: text

    define {devicename}.gBridge MQTT_BRIDGE {devicename}
    attr {devicename}.gBridge IODev Connections.gBridge
    attr {devicename}.gBridge publishReading_onoff gBridge/u{userid}/d{deviceid}/onoff/set
    attr {devicename}.gBridge publishReading_pct gBridge/u{userid}/d{deviceid}/brightness/set
    attr {devicename}.gBridge stateFormat transmission-state
    attr {devicename}.gBridge subscribeSet_gstate {if($message eq "0"){fhem("set $device off")}else{ fhem("set $device on")};; 0} gBridge/u{userid}/d{deviceid}/onoff
    attr {devicename}.gBridge subscribeSet_pct gBridge/u{userid}/d{deviceid}/brightness
