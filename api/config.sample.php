<?php
    //the Client ID you've entered in the Google Actions Console
    $google_clientid = 'GoogleClientID';

    //Google Project ID is shown in the "Settings" in the Google Actions Console.
    //It usually consists of your project name and some Hex-Number, for example "kappelt-smarthome-eb8cd"
    $google_projectid = 'kappelt-smarthome-eb8cd';

    //MQTT broker settings
    $mqtt_server = 'mqtt1.int.kappelt.net';
    $mqtt_port = 1883;
    //leave username and password empty if it is not required
    $mqtt_username = '';
    $mqtt_password = '';

    //MySQL-Server that contains the main database
    $mysql_database = 'gbridge';
    $mysql_server = 'db1.int.kappelt.net';
    $mysql_user = 'gbridge';
    $mysql_password = 'passw0rd';
?>