<?php
    //the Client ID you've entered in the Google Actions Console
    $google_clientid = 'GoogleClientID';

    //Google Project ID is shown in the "Settings" in the Google Actions Console.
    //It usually consists of your project name and some Hex-Number, for example "kappelt-smarthome-eb8cd"
    $google_projectid = 'kappelt-smarthome-eb8cd';

    //Connection parameters for the redis server
    //Look at the Predis-Documentation for information about other supported parameters: https://github.com/nrk/predis/wiki/Connection-Parameters
    $redis_connection = 'tcp://redis1.int.kappelt.net:6379';

    //MySQL-Server that contains the main database
    $mysql_database = 'gbridge';
    $mysql_server = 'db1.int.kappelt.net';
    $mysql_user = 'gbridge';
    $mysql_password = 'passw0rd';
?>