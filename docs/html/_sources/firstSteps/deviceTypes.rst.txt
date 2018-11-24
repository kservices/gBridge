Device Types
=================

The following **device types** are currently supported by gBridge:

:Light: A smart lightbulb, for instance
:Outlet: A switchable outlet
:Switch: A general definition for all kinds of switching devices
:Scene: Triggers pre-configured settings for various lights/ thermostats/ etc., e.g. for different moods
:Thermostat: Device that controls the heating, ventilation or an air conditioner.

Each device supports one or more so called **traits**. Those are feature sets that the device supports.

In theory, every kind of device could be combined with each kind of trait. However, not every combination is meaningfull and controllable by voice commands.

These traits are supported:

:On and Off: Turns a device on or off, obviously
:Brightness: Set the brightness percentage of a device
:Scene: Trait, that allows this device to be triggered
:Temperature Control: Enables features that are typical for thermostats and alike

Light
--------------

Recommended Traits
............................
On and Off, Brightness

Example Commands
.....................
* Turn on/ off {light name}
* Dim {light name}
* Brighten (the) {light name}
* Set {light name} to x %
* Brighten/ Dim {light name} by x %
* Turn on/ off lights in {room name}
* Is {light name} turned on?



.. _DeviceTypeOutlet:

Outlet
--------------

Recommended Trait
............................
On and Off

Example Commands
.....................
* Turn on/ off {plug/ outlet name}
* Is {plug/ outlet name} turned on?

Switch
--------------
See `Outlet`_.

Scene
-------------------

Recommended Trait
............................
Scene

Example Commands
.....................
* Active/ Start {scene name}

Thermostat
-------------------

Recommended Trait
............................
Temperature Setting

Example Commands
.....................
* Make it warmer/ cooler
* Raise/ lower the Temperature
* Raise/ lower the temp x degrees
* Set the temperature to x degrees
* How warm is it in here?
* What's the humidity of {thermostat name} (*if humidity is enabled*)