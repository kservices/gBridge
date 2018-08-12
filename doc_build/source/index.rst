.. Kappelt gBridge documentation master file, created by
   sphinx-quickstart on Sat Jun 23 18:42:27 2018.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

Introduction
===========================================

*Kappelt gBridge* allows you to control (almost) every smart home device with Google Assistant. It provides a bridge between Google Assistant and MQTT.

.. TIP:: 
   *Kappelt gBridge* is an open source application. However, I provide an inexpensive hosted appliance of gBridge. 
   **This is a great way of supporting my work** - and you don't have to care about installation, debugging and maintenance.

   Interested? Have a look at `https://about.gbridge.kappelt.net <https://about.gbridge.kappelt.net>`_.

It acts like a smart home device provider, that is listed in the Google Home app. But there are no real devices by Kappelt gBridge - there are virtual devices defined by you in your user account's dashboard.
When you interact with a virtual device you've defined ("Hey Google, turn on the lights in the living room"), gBridge will publish an MQTT message.

This MQTT message can finally be handled by most smart home applications and might be further processed there.

All set? :doc:`Let's get started <firstSteps/gettingStarted>` 

.. toctree::
   :maxdepth: 2
   :caption: First steps:

   self
   firstSteps/gettingStarted
   firstSteps/deviceMechanism
   firstSteps/mqttServer
   exampleUsage/fhem.rst
   selfHosted/hostItYourself.rst
