<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TraitType extends Model
{
    /**
     * Model stored in table 'trait_type'
     */
    protected $table = 'trait_type';

    /**
     * The primary key is here 'traittype_id' not the default 'id'
     */
    protected $primaryKey = 'traittype_id';

    /**
     * Timestamps are unnecessary
     */
    public $timestamps = false;

    public function devices()
    {
        //This is a m:n relation, joined by the table trait
        return $this->belongsToMany(\App\Models\Device::class, 'trait', 'traittype_id', 'device_id')->withPivot('trait_id', 'config', 'mqttActionTopic', 'mqttStatusTopic');
    }

    public function getAvailableFanSpeeds()
    {
        return Collection::make(json_decode($this->pivot->config, true))->get('availableFanSpeeds');
    }

    public function getAvailableFanSpeedsAsString()
    {
        $fanSpeeds = $this->getAvailableFanSpeeds();

        if (is_null($fanSpeeds)) {
            return [];
        }

        $dataString = [];
        foreach ($fanSpeeds as $speedName => $speedConf) {
            if (is_null($speedConf['names']) || ! count($speedConf['names'])) {
                $dataString[] = "$speedName:Speed $speedName";
            } else {
                $dataString[] = "$speedName:".implode(',', $speedConf['names']);
            }
        }

        return $dataString;
    }

    /**
     * For trait type "FanSpeed": Parse available speeds in an array of strings, each formatted like:
     * speed_value:speed_name1,speed_name2,...
     * It returns true upon successfull parsing, false if there was an error
     */
    public function setAvailableFanSpeedsFromString($data)
    {
        if ($this->shortname != 'FanSpeed') {
            return false;
        }

        $availableFanSpeeds = [];
        foreach ($data as $line) {
            if ($line == '') {
                continue;
            }
            [$speed_value, $speed_names] = explode(':', $line, 2);
            if (is_null($speed_names) || ($speed_names == '')) {
                return false;
            }
            $speed_names = str_replace("\r", '', $speed_names);
            $speed_names = explode(',', $speed_names);

            $availableFanSpeeds[$speed_value] = ['names' => $speed_names];
        }

        $this->pivot->config = json_encode([
            'availableFanSpeeds' => $availableFanSpeeds,
        ]);
        $this->pivot->save();
        $this->save();

        return true;
    }

    /**
     * For trait type "FanSpeed": Parse available fan speeds from an array of objects, in the form:
     * [
     *  "speed_value1" => ["speed1_name1", "speed1_namex"],
     *  "speed_value2" => ["speed2_name1", "speed2_namex"],
     * ]
     * It returns true upon successfull parsing, false if there was an error
     */
    public function setAvailableFanSpeedsFromObjectArray($data)
    {
        if ($this->shortname != 'FanSpeed') {
            return false;
        }

        if (! is_array($data)) {
            //the object itself has to be an array of objects
            return false;
        }

        $availableFanSpeeds = [];
        foreach ($data as $speedVal => $speedNames) {
            if (! is_array($speedNames)) {
                //speed names need to be an array, too
                return false;
            }

            $speedNamesNormalized = [];
            foreach ($speedNames as $speedName) {
                if (! is_string($speedName)) {
                    return false;
                }
                $speedNamesNormalized[] = $speedName;
            }

            $availableFanSpeeds[$speedVal] = ['names' => $speedNamesNormalized];
        }

        $this->pivot->config = json_encode([
            'availableFanSpeeds' => $availableFanSpeeds,
        ]);
        $this->pivot->save();
        $this->save();

        return true;
    }

    /**
     * Set the config for the trait "CameraStream".
     * Format is one of: "progressive_mp4", "hls", "dash", "smooth_stream"
     * Default URL is used if no different URL has been specified via MQTT. Can be null or empty
     */
    public function setCameraStreamConfig($format, $default_url)
    {
        if ($this->shortname != 'CameraStream') {
            return;
        }

        if ($default_url == '') {
            $default_url = null;
        }

        $this->pivot->config = json_encode([
            'cameraStreamFormat' => $format,
            'cameraStreamDefaultUrl' => $default_url,
        ]);
        $this->pivot->save();
        $this->save();
    }

    /**
     * Get the config for the trait "CameraStream"
     * Returns an associative array:
     *  'cameraStreamFormat': One of "progressive_mp4", "hls", "dash", "smooth_stream"
     *  'cameraStreamDefaultUrl': Default URL to be used, or null
     */
    public function getCameraStreamConfig()
    {
        $retval = [
            'cameraStreamFormat' => null,
            'cameraStreamDefaultUrl' => null,
        ];

        if ($this->shortname != 'CameraStream') {
            return $retval;
        }

        $config = Collection::make(json_decode($this->pivot->config, true));
        if ($config->get('cameraStreamFormat')) {
            $retval['cameraStreamFormat'] = $config->get('cameraStreamFormat');
        } else {
            $retval['cameraStreamFormat'] = 'hls';
        }

        if ($config->get('cameraStreamDefaultUrl')) {
            $retval['cameraStreamDefaultUrl'] = $config->get('cameraStreamDefaultUrl');
        }

        return $retval;
    }

    /**
     * Returns this trait as an object compliant with the gBridge API V2 Spec
     */
    public function toApiV2Object($userid)
    {
        $jsoninfo = [
            'type' => $this->shortname,
            'requiresActionTopic' => $this->needsActionTopic ? true : false,
            'requiresStatusTopic' => $this->needsStatusTopic ? true : false,
        ];

        if ($this->needsActionTopic) {
            $jsoninfo['actionTopic'] = "gBridge/u$userid/".$this->pivot->mqttActionTopic;
        }
        if ($this->needsStatusTopic) {
            $jsoninfo['statusTopic'] = "gBridge/u$userid/".$this->pivot->mqttStatusTopic;
        }

        if ($this->shortname === 'TempSet.Mode') {
            $jsoninfo['modesSupported'] = [];

            $config = json_decode($this->pivot->config, true);
            if (array_key_exists('modesSupported', $config)) {
                if (! is_null($config['modesSupported'])) {
                    $jsoninfo['modesSupported'] = $config['modesSupported'];
                }
            }
        }

        if ($this->shortname === 'TempSet.Humidity') {
            $config = json_decode($this->pivot->config, true);
            if (array_key_exists('humiditySupported', $config) && $config['humiditySupported']) {
                $jsoninfo['humiditySupported'] = true;
            } else {
                $jsoninfo['humiditySupported'] = false;
            }
        }

        if ($this->shortname === 'FanSpeed') {
            $speeds = $this->getAvailableFanSpeeds();

            //necessary: remap fan speeds for numeric indexes
            $speedObj = new \stdClass();

            foreach ($speeds as $speedVal => $speedConfig) {
                $speedObj->{$speedVal} = $speedConfig['names'];
            }

            $jsoninfo['fanSpeeds'] = $speedObj;
        }

        if ($this->shortname === 'CameraStream') {
            $streamConf = $this->getCameraStreamConfig();

            $jsoninfo['streamFormat'] = $streamConf['cameraStreamFormat'];
            $jsoninfo['streamDefaultUrl'] = $streamConf['cameraStreamDefaultUrl'];
        }

        return $jsoninfo;
    }
}
