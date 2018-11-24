Hosted MQTT server
=============================

.. NOTE::
   This page is written for the hosted gBridge service.

Authentication
-----------------
Of course, the gBridge MQTT servers requires authentication.

**Username:** :code:`gbridge-{userid}`. Your full username is shown in your account's dashboard under "My Account". *Example:* :code:`gbridge-u4`

**Password:** Your account's password is the default MQTT password. You may change the MQTT password independently from your account's password in the dashboard. This password must be at least 8 characters long, while containing both upper- and lowercase letters, a number and a special character. A new password will (almost) instantly apply for any new MQTT connections.

Available protocols
---------------------

The hosted gBridge MQTT servers can be reached over many ways.

MQTT over TLS
~~~~~~~~~~~~~~~~
MQTT over TLS can be reached by :code:`mqtts://mqtt.gbridge.kappelt.net`

:Server: mqtt.gbridge.kappelt.net
:Port: 8883
:MQTT Protocol: Version 3.1
:TLS: TLS V1.2 is required
:Authentication: required, as described above

The server uses certificates signed by Let's Encrypt. This CA is trusted by most systems, your shouldn't need to do anything more than specifying your system's CA directory.

For many linux-based systems, trusted certificates are located under :code:`/etc/ssl/certs/`. For example, when using :code:`mosquitto_sub`  or :code:`mosquitto_pub`, you just need to give the parameter :code:`--capath /etc/ssl/certs/`

MQTT over secure websockets
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
MQTT over secure websockets can be reached by :code:`wss://mqtt.gbridge.kappelt.net`

:Server: mqtt.gbridge.kappelt.net
:Port: 443
:MQTT Protocol: Version 3.1
:TLS: TLS V1.2 is required
:Authentication: required, as described above

The same certificates as for MQTT over TLS are used, so read the chapter above for more information.

Plain MQTT
~~~~~~~~~~~~~~
.. WARNING::
   Unencrypted MQTT, especially over the (public, bad) internet, should *never* be used by any means. Support for plain MQTT can be canceled at any time, without any notice.

Plain, unsecure MQTT can be reached by :code:`mqtt://mqtt.gbridge.kappelt.net`

:Server: mqtt.gbridge.kappelt.net
:Port: 1883
:MQTT Protocol: Version 3.1
:Authentication: required, as described above

MQTT over websockets
~~~~~~~~~~~~~~~~~~~~~~
.. WARNING::
   Unencrypted MQTT, especially over the (public, bad) internet, should *never* be used by any means. Support for MQTT over (unsecured) websockets can be canceled at any time, without any notice.

Plain, unsecure MQTT can be reached by :code:`ws://mqtt.gbridge.kappelt.net`

:Server: mqtt.gbridge.kappelt.net
:Port: 80
:MQTT Protocol: Version 3.1
:Authentication: required, as described above

.. _mqttServer-mosquittoBridge:

Using own Mosquitto as a Bridge
-----------------------------------

Kappelt gBridge should *never* be accessed via an unencrypted port. However, some devices might not support MQTT over TLS. In some cases, it is acceptabled to provide an unencrypted MQTT server in your own network/ intranet.

If you are already have an Mosquitto instance running in your own network, you can use it as a so called "Bridge". Only the Mosquitto server in your network will connect to the gBridge MQTT server. All devices in your network will connect to your own MQTT server - but they'll receive messages from both your own MQTT server and the gBridge server.

When your own Mosquitto instance receives data from the gBridge servers, that match a certain pattern, it'll be forwarded to the devices connected in your local network (and vice versa).

.. figure:: ../_static/scheme-mosquitto-bridge.png
   :width: 100%
   :align: center
   :alt: Local request for gBridge are proxied through your local mosquitto server.
   :figclass: align-center

   Any data from gBridge is proxied by your local Mosquitto instance and then forwarded to your local devices. This works the other way around, too.

The following configuration works with Mosquitto. Place it at the end of your Mosquitto configuration (often under :code:`/etc/mosquitto/mosquitto.conf`) or in a separate file, that will be included. Replace the parameters in curly brackets with your appropriate information.

.. code-block:: aconf

    connection kappelt-gbridge
    address mqtt.gbridge.kappelt.net:8883
    bridge_attempt_unsubscribe true
    bridge_protocol_version mqttv31
    cleansession true
    remote_username {gbridge-mqtt-username}
    remote_password {gbridge-mqtt-password}

    topic gBridge/u{gbridge-userid}/+/+ both 0 "" ""
    topic gBridge/u{gbridge-userid}/+/+/set both 0 "" ""
    #you might need to change the path of the CA files
    bridge_capath /etc/ssl/certs/
    bridge_tls_version tlsv1.2

Restart your mosquitto instance. Have a look in its log file. It should show no errors, if everything went well:

.. code-block:: bash

    pi@hcpi01:~ $ sudo tail /var/log/mosquitto/mosquitto.log -n 20
    1532876260: mosquitto version 1.4.10 (build date Fri, 22 Dec 2017 08:19:25 +0000) starting
    1532876260: Config loaded from /etc/mosquitto/mosquitto.conf.
    1532876260: Opening ipv4 listen socket on port 1883.
    1532876260: Opening ipv6 listen socket on port 1883.
    1532876260: Connecting bridge kappelt-gbridge (mqtt.gbridge.kappelt.net:8883)
    1532876260: New connection from 192.168.2.151 on port 1883.
    1532876260: New client connected from 192.168.2.151 as KH102_BC73E4 (c1, k15, u'DVES_USER').
    [...]

Now, subscribe to a device topic of gBridge (like :code:`gBridge/u1/d1/onoff`), but do *not* connect to the gBridge MQTT server (:code:`mqtt.gbridge.kappelt.net`), connect to your local MQTT server instead.

If everything is OK, you should now receive messages from your local server as you would from the gBridge server.
