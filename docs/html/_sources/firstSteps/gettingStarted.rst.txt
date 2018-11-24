.. _gettingStarted:

Getting Started
====================

.. NOTE::
   This manual is made with the hosted gBridge service. If you are running gBridge on your own servers, there might be some small differences. However, they can be easily adapted.

Register a new account
-----------------------
That's not complicated at all: just visit `https://gbridge.kappelt.net/register <https://gbridge.kappelt.net/register>`_ and fill in the required information. After you've received the confirmation email, you can instantly log in.

A first device
--------------------
You'll see your account's dashboard for the first time after logging in.

.. figure:: ../_static/empty-dashboard-devices.png
   :width: 100%
   :align: center
   :alt: No devices have been created yet.
   :figclass: align-center

   Your dashboard

Press the top-right button labeled with "+ Device" to create a new one. Choose whatever you like. For this example, I've chosen "Light" as the type and both "On and Off" and "Brightness" as supported traits.

.. figure:: ../_static/dashboard-first-device.png
   :width: 100%
   :align: center
   :alt: You've just created a first device.
   :figclass: align-center

   Congratulations! You've just created your first virtual device.

Note the MQTT topics that are listed here.

.. TIP::
   You can edit these MQTT topics for the devices as you like. To do so, press the "Edit" button in the device list.

   Please note that you can't specify your own topics while creating new devices, only while editing them after creation.

Connect Google Assistant
-----------------------------
Open the *Google Home App* to connect your Google Home system to gBridge. Add a new device, select the category "Works with Google". Select *Kappelt gBridge*.

.. figure:: ../_static/googlehome-add-provider.png
   :width: 50%
   :align: center
   :alt: You can add Kappelt gBridge as a smart home provider in the Google Home app.
   :figclass: align-center

.. figure:: ../_static/googlehome-link-account.png
   :width: 50%
   :align: center
   :alt: You need to enter your account's email and an accesskey.
   :figclass: align-center

   Enter your email and your password.

.. figure:: ../_static/googlehome-device-listed.png
   :width: 50%
   :align: center
   :alt: Devices are listed in the Google Home app
   :figclass: align-center

   Your newly created devices will appear in the list. If you like, you can assign a room to the devices.

If you add new devices in your dashboard, they'll appear in the list automatically.

.. IMPORTANT::
   There is a bug in a current version of the Google Home app that might lead to the message "Couldn't update the settings. Check your connection". This problem is solely caused by the Google Home app, thus we are unable to fix it at the moment.

   This issue has been analyzed by `some Reddit users <https://www.reddit.com/r/googlehome/comments/7npsz8/psa_solutions_to_the_couldnt_update_the_settings/>`_. It seems to be common among Android devices with Google Chrome as the standard browser, while being logged in to multiple Google Accounts.

   For many users, installing Firefox and setting it (temporarily) to the default browser seemed to fixed the problem.

Test it
---------
Everything is ready now! Messages will now be available on gBridge's public MQTT server. You can connect to it:

:Hostname: mqtt.gbridge.kappelt.net
:Port: 8883
:Username: Shown in your account's dashboard under "My Account"
:Password: Is your account's password by default, but can be changed independently.
:TLS: TLS V1.2 is required

**About TLS:** The Server uses an certificate that is signed by Let's Encrypt. 
The Let's Encrypt CA is trusted by most systems nowadays, you shouldn't really need to install a certificate. 
`Only download the prepared CA certificates <https://about.gbridge.kappelt.net/static/LetsEncrypt-AllCAs.pem>`_ if your system doesn't support them natively. 

Subscribe now to the MQTT topic that belongs to your device, for example with *mosquitto_sub*:

.. code-block:: bash

   mosquitto_sub --username your-mqtt-username \
      --pw your-mqtt-password \
      --capath /etc/ssl/certs/ \
      --host mqtt.gbridge.kappelt.net \
      --port 8883 \
      --topic gBridge/u2/d4/onoff

.. figure:: ../_static/googlehome-try-it.png
   :width: 100%
   :align: center
   :alt: Google Assistant Example
   :figclass: align-center

   A voice command leads to a published MQTT message.

Going further
----------------
Now you're all set! There is more information in this documentation.

Have a look at https://status.gbridge.kappelt.net, too. You can subscribe to notifications about planned updates or possible service outages.

If you like, you can follow us on Twitter: https://twitter.com/Kappelt_gBridge