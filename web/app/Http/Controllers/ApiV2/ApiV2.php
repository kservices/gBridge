<?php

namespace App\Http\Controllers\ApiV2;

use Illuminate\Support\Str;
use App\Device;
use App\DeviceType;
use App\Http\Controllers\Controller;
use App\TraitType;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class ApiV2 extends Controller
{
    /**
     * Create a new ApiV2 instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:apiv2', ['except' => []]);
    }

    /**
     * Store general information about the user in the redis cache for usage with other modules of gBridge
     *
     * @param  User  $user
     */
    private function userInfoToCache(User $user)
    {
        $deviceinfo = [];
        foreach ($user->devices as $device) {
            $deviceinfo[$device->device_id] = [];
            foreach ($device->traits as $trait) {
                $deviceinfo[$device->device_id][$trait->shortname] = [
                    'actionTopic' => $trait->pivot->mqttActionTopic,
                    'statusTopic' => $trait->pivot->mqttStatusTopic,
                ];
            }
        }
        $userid = $user->user_id;
        Redis::set("gbridge:u$userid:devices", json_encode($deviceinfo));
    }

    /**
     * Return a list of all devices to the user
     */
    public function getDevices(Request $request)
    {
        $user = auth('apiv2')->user();
        $devices = $user->devices()->get()->toArray();

        $devices = array_map(function ($device) {
            return [
                'device_id' => $device['device_id'],
                'name' => $device['name'],
            ];
        }, $devices);

        return response()->json($devices);
    }

    /**
     * Get information about a specified device
     */
    public function getDeviceById(Request $request, $deviceid)
    {
        $deviceid = intval($deviceid);

        if ($deviceid === 0) {
            return $this->errorResponse(400, 'device_invalid_id', 'Your provided device id is invalid or malformed');
        }

        $device = auth('apiv2')->user()->devices()->find($deviceid);

        if (! $device) {
            return $this->errorResponse(404, 'device_not_found', 'The device you are asking for does not exist');
        }

        return response()->json($device->toApiV2Object(auth('apiv2')->user()->user_id));
    }

    /**
     * Users may send their MQTT topics with the prefix "gBridge/u{userid}/
     * This is removed here since they are handled with this internally
     */
    private function removeGbridgePrefixFromMqttTopic($userid, $mqttTopic)
    {
        return preg_replace("/^gBridge\/u$userid\//", '', $mqttTopic);
    }

    /**
     * Check if the given object is an array of only strings
     */
    private function checkIfVariableIsStringArray($arr)
    {
        if (! is_array($arr)) {
            return false;
        }

        return array_sum(array_map('is_string', $arr)) == count($arr);
    }

    /**
     * Validates MQTT topic
     * It shall only contain alphanumeric characters, slashes, dashes and underscores
     *
     * @return true if it is ok
     */
    private function validateMqttTopic($mqttTopic)
    {
        return preg_match('/^[a-z0-9-_\/]+$/i', $mqttTopic);
    }

    /**
     * Update the device incrementally
     */
    public function updateDeviceById(Request $request, $deviceid)
    {
        $userid = auth('apiv2')->user()->user_id;

        $deviceid = intval($deviceid);

        if ($deviceid === 0) {
            return $this->errorResponse(400, 'device_invalid_id', 'Your provided device id is invalid or malformed');
        }

        $device = auth('apiv2')->user()->devices()->find($deviceid);

        if (! $device) {
            return $this->errorResponse(404, 'device_not_found', 'The device you are asking for does not exist');
        }

        if ($request->input('name')) {
            $device->name = $request->input('name');
        }
        if ($request->input('type')) {
            $requestedDeviceType = strtolower($request->input('type'));

            if (! in_array($requestedDeviceType, ['light', 'outlet', 'switch', 'scene', 'thermostat', 'fan', 'ac', 'purifier', 'sprinkler', 'door', 'blinds', 'shutter', 'dishwasher', 'dryer', 'vacuum', 'washer', 'camera'])) {
                return $this->errorResponse(400, 'device_invalid_type', 'Invalid type specified');
            }

            $device->devicetype_id = DeviceType::whereRaw('lower(shortname) like (?)', ["%{$requestedDeviceType}%"])->first()->devicetype_id;
        }
        if ($request->has('twofa')) {
            if (is_null($request->input('twofa')) || ($request->input('twofa') === 'none')) {
                $device->twofa_type = null;
            } elseif (strtolower($request->input('twofa')) === 'ack') {
                $device->twofa_type = 'ack';
            } elseif (strtolower($request->input('twofa')) === 'pin') {
                $device->twofa_type = 'pin';
                if (is_null($device->twofa_pin) && is_null($request->input('twofa_pin'))) {
                    return $this->errorResponse(400, 'twofa_pin_code_required', 'A twofa pin code is required if the selected type is pin and no code is saved for this device yet');
                }
            } else {
                return $this->errorResponse(400, 'twofa_invalid_type', 'Invalid Twofa type given');
            }
        }
        if ($request->has('twofaPin')) {
            if (is_null($request->input('twofaPin')) && ($device->twofa_type === 'pin')) {
                return $this->errorResponse(400, 'twofa_pin_code_required', 'A twofa pin code is required if the selected type is pin');
            }

            $device->twofa_pin = strval($request->input('twofaPin'));
            if (is_null($request->input('twofaPin'))) {
                $device->twofa_pin = null;
            }
        }
        if ($request->has('traits')) {
            $requestedTraits = $request->input('traits');

            //CUSTOM HANDLING FOR TempSet.* trait
            //filter for all traits that belong to a thermostat (TempSet.*)
            $thermostatyTraits = array_map(function ($element) {
                if (array_key_exists('type', $element)) {
                    return $element['type'];
                } else {
                    return 0;
                }
            }, $requestedTraits);
            $thermostatyTraits = array_values(array_filter($thermostatyTraits, function ($element) {
                return in_array($element, ['TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity'], true);
            }));
            if (count($thermostatyTraits)) {
                //User has specified one or more traits starting with TempSet.*
                //If one is specified, the internal mechanism requires all 4 options to exist
                foreach (['TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity'] as $thermostatRequiredTrait) {
                    if (! in_array($thermostatRequiredTrait, $thermostatyTraits)) {
                        return $this->errorResponse(400, 'generic', 'When giving one trait starting with TempSet.*, you need to give its belonging ones, too: TempSet.Mode, TempSet.Setpoint, TempSet.Ambient, TempSet.Humidity');
                    }
                }
            }
            //END CUSTOM HANDLING FOR TempSet.* trait

            $newTraits = [];

            foreach ($requestedTraits as $requestedTrait) {
                if (! array_key_exists('type', $requestedTrait)) {
                    return $this->errorResponse(400, 'trait_type_unspecified', 'No type is specified for one or more traits');
                }

                $requestedTraitType = $requestedTrait['type'];

                if (! in_array($requestedTraitType, ['OnOff', 'Brightness', 'Scene', 'TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity', 'FanSpeed', 'StartStop', 'OpenClose', 'CameraStream'])) {
                    return $this->errorResponse(400, 'trait_type_unknown', 'Unknown trait type given');
                }

                $traitType = TraitType::where('shortname', $requestedTraitType)->first();
                $traitTypeId = $traitType->traittype_id;
                $traitShortname = str_replace('.', '-', $traitType->shortname);
                $newTraits[$traitTypeId] = [];

                /**
                 * Three options for action/ status topic and trait related properties
                 * 1. User has given a new topic that overrides the current one (or is used as a new one)
                 * 2. This trait already exists for the device and it has a topic setting, but the user hasn't specified one
                 * 3. We use the default topic
                 */

                //action topic
                if ($traitType->needsActionTopic && array_key_exists('actionTopic', $requestedTrait)) {
                    if (! $this->validateMqttTopic($requestedTrait['actionTopic'])) {
                        return $this->errorResponse(400, 'mqtt_invalid_topic', 'The MQTT topic may only contain alphanumeric characters, slashes, dashes and underscores');
                    }
                    $newTraits[$traitTypeId]['mqttActionTopic'] = $this->removeGbridgePrefixFromMqttTopic($userid, $requestedTrait['actionTopic']);
                } elseif ($device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                    $newTraits[$traitTypeId]['mqttActionTopic'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttActionTopic;
                } else {
                    $newTraits[$traitTypeId]['mqttActionTopic'] = 'd'.$device->device_id.'/'.strtolower($traitShortname);
                }

                //status topic
                if ($traitType->needsStatusTopic && array_key_exists('statusTopic', $requestedTrait)) {
                    if (! $this->validateMqttTopic($requestedTrait['statusTopic'])) {
                        return $this->errorResponse(400, 'mqtt_invalid_topic', 'The MQTT topic may only contain alphanumeric characters, slashes, dashes and underscores');
                    }
                    $newTraits[$traitTypeId]['mqttStatusTopic'] = $this->removeGbridgePrefixFromMqttTopic($userid, $requestedTrait['statusTopic']);
                } elseif ($device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                    $newTraits[$traitTypeId]['mqttStatusTopic'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttStatusTopic;
                } else {
                    $newTraits[$traitTypeId]['mqttStatusTopic'] = 'd'.$device->device_id.'/'.strtolower($traitShortname).'/set';
                }

                //available modes for trait "TempSet.Mode"
                if ($traitType->shortname === 'TempSet.Mode') {
                    if (array_key_exists('modesSupported', $requestedTrait)) {
                        if (! $this->checkIfVariableIsStringArray($requestedTrait['modesSupported'])) {
                            return $this->errorResponse(400, 'generic', 'Malformed data for modesSupported');
                        }
                        foreach ($requestedTrait['modesSupported'] as $requestedMode) {
                            if (! in_array($requestedMode, ['off', 'heat', 'cool', 'on', 'auto', 'fan-only', 'purifier', 'eco', 'dry'])) {
                                return $this->errorResponse(400, 'generic', 'Unknown mode requested in modesSupported');
                            }
                        }

                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'modesSupported' => array_values(array_unique($requestedTrait['modesSupported'])),
                        ]);
                    } elseif ($device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                        $newTraits[$traitTypeId]['config'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->config;
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'modesSupported' => ['off', 'heat', 'on', 'auto'],
                        ]);
                    }
                }

                if ($traitType->shortname === 'TempSet.Humidity') {
                    if (array_key_exists('humiditySupported', $requestedTrait)) {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'humiditySupported' => $requestedTrait['humiditySupported'] ? true : false,
                        ]);
                    } elseif ($device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                        $newTraits[$traitTypeId]['config'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->config;
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'humiditySupported' => false,
                        ]);
                    }
                }

                if ($traitType->shortname === 'FanSpeed') {
                    if (array_key_exists('fanSpeeds', $requestedTrait)) {
                        $fanSpeeds = $requestedTrait['fanSpeeds'];

                        if (! is_array($fanSpeeds)) {
                            //the object itself has to be an array of objects
                            return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                        }

                        //workaround needed: $availableFanSpeeds as objects, so that even numeric keys get saved as a string
                        $availableFanSpeeds = new \stdClass();
                        foreach ($fanSpeeds as $speedVal => $speedNames) {
                            if (! is_array($speedNames)) {
                                //speed names need to be an array, too
                                return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                            }

                            $speedNamesNormalized = [];
                            foreach ($speedNames as $speedName) {
                                if (! is_string($speedName)) {
                                    return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                                }
                                $speedNamesNormalized[] = $speedName;
                            }

                            $availableFanSpeeds->{strval($speedVal)} = ['names' => $speedNamesNormalized];
                        }

                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'availableFanSpeeds' => $availableFanSpeeds,
                        ]);
                    } elseif ($device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                        $newTraits[$traitTypeId]['config'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->config;
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'availableFanSpeeds' => ['S1' => ['names' => ['Slow']], 'S2' => ['names' => ['Medium']], 'S3' => ['names' => ['Fast']]],
                        ]);
                    }
                }

                if ($traitType->shortname === 'CameraStream') {
                    $streamFormat = 'progressive_mp4';
                    $streamDefaultUrl = null;

                    if (array_key_exists('streamFormat', $requestedTrait)) {
                        $streamFormat = $requestedTrait['streamFormat'];
                        if (! in_array($streamFormat, ['progressive_mp4', 'hls', 'dash', 'smooth_stream'])) {
                            return $this->errorResponse(400, 'generic', 'Invalid stream format given');
                        }
                    }
                    if (array_key_exists('streamDefaultUrl', $requestedTrait)) {
                        $streamDefaultUrl = $requestedTrait['streamDefaultUrl'];
                    }

                    $newTraits[$traitTypeId]['config'] = json_encode([
                        'cameraStreamFormat' => $streamFormat,
                        'cameraStreamDefaultUrl' => $streamDefaultUrl,
                    ]);

                    if (! array_key_exists('streamFormat', $requestedTrait) &&
                        ! array_key_exists('streamDefaultUrl', $requestedTrait) &&
                        $device->traits->where('traittype_id', $traitType->traittype_id)->count()) {
                        $newTraits[$traitTypeId]['config'] = $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->config;
                    }
                }
            }

            $device->traits()->sync($newTraits);
        }

        $device->save();

        $this->userInfoToCache(auth('apiv2')->user());

        $device = $device->fresh();

        return response()->json($device->toApiV2Object(auth('apiv2')->user()->user_id));
    }

    /**
     * Create a new device
     */
    public function createDevice(Request $request)
    {
        $userid = auth('apiv2')->user()->user_id;
        $user = User::find($userid);

        $device = new Device();

        if ($request->input('name')) {
            $device->name = $request->input('name');
        } else {
            return error_response(400, 'device_name_required', 'A name for the device is required');
        }

        if ($request->input('type')) {
            $requestedDeviceType = strtolower($request->input('type'));

            if (! in_array($requestedDeviceType, ['light', 'outlet', 'switch', 'scene', 'thermostat', 'fan', 'ac', 'purifier', 'sprinkler', 'door', 'blinds', 'shutter', 'dishwasher', 'dryer', 'vacuum', 'washer', 'camera'])) {
                return $this->errorResponse(400, 'device_invalid_type', 'Invalid type specified');
            }

            $device->devicetype_id = DeviceType::whereRaw('lower(shortname) like (?)', ["%{$requestedDeviceType}%"])->first()->devicetype_id;
        } else {
            return error_response(400, 'device_type_required', "You need to specify the device's type");
        }

        if ($request->has('twofa')) {
            if (is_null($request->input('twofa')) || ($request->input('twofa') === 'none')) {
                $device->twofa_type = null;
            } elseif (strtolower($request->input('twofa')) === 'ack') {
                $device->twofa_type = 'ack';
            } elseif (strtolower($request->input('twofa')) === 'pin') {
                $device->twofa_type = 'pin';
                if (is_null($request->input('twofa_pin'))) {
                    return $this->errorResponse(400, 'twofa_pin_code_required', 'A twofa pin code is required if the selected type is pin');
                }
            } else {
                return $this->errorResponse(400, 'twofa_invalid_type', 'Invalid Twofa type given');
            }
        }

        if ($request->has('twofaPin')) {
            if (is_null($request->input('twofaPin'))) {
                return $this->errorResponse(400, 'twofa_pin_code_required', 'A twofa pin code is required if the selected type is pin');
            }

            $device->twofa_pin = strval($request->input('twofaPin'));
        }

        $newTraits = [];

        if (! $request->has('traits')) {
            return $this->errorResponse(400, 'traits_required', 'You need to specify at least one trait for this device.');
        }

        //We need to save device here already because the following functions need access to its device id
        $device->user()->associate($user);
        $device->save();

        if ($request->has('traits')) {
            $requestedTraits = $request->input('traits');

            //CUSTOM HANDLING FOR TempSet.* trait
            //filter for all traits that belong to a thermostat (TempSet.*)
            $thermostatyTraits = array_map(function ($element) {
                if (array_key_exists('type', $element)) {
                    return $element['type'];
                } else {
                    return 0;
                }
            }, $requestedTraits);
            $thermostatyTraits = array_values(array_filter($thermostatyTraits, function ($element) {
                return in_array($element, ['TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity'], true);
            }));
            if (count($thermostatyTraits)) {
                //User has specified one or more traits starting with TempSet.*
                //If one is specified, the internal mechanism requires all 4 options to exist
                foreach (['TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity'] as $thermostatRequiredTrait) {
                    if (! in_array($thermostatRequiredTrait, $thermostatyTraits)) {
                        $device->delete();

                        return $this->errorResponse(400, 'generic', 'When giving one trait starting with TempSet.*, you need to give its belonging ones, too: TempSet.Mode, TempSet.Setpoint, TempSet.Ambient, TempSet.Humidity');
                    }
                }
            }
            //END CUSTOM HANDLING FOR TempSet.* trait

            foreach ($requestedTraits as $requestedTrait) {
                if (! array_key_exists('type', $requestedTrait)) {
                    $device->delete();

                    return $this->errorResponse(400, 'trait_type_unspecified', 'No type is specified for one or more traits');
                }

                $requestedTraitType = $requestedTrait['type'];

                if (! in_array($requestedTraitType, ['OnOff', 'Brightness', 'Scene', 'TempSet.Mode', 'TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity', 'FanSpeed', 'StartStop', 'OpenClose', 'CameraStream'])) {
                    $device->delete();

                    return $this->errorResponse(400, 'trait_type_unknown', 'Unknown trait type given');
                }

                $traitType = TraitType::where('shortname', $requestedTraitType)->first();
                $traitTypeId = $traitType->traittype_id;
                $traitShortname = str_replace('.', '-', $traitType->shortname);

                $newTraits[$traitTypeId] = [];

                /**
                 * Two options for action/ status topic and trait related properties
                 * 1. User has given a new topic that overrides the current one (or is used as a new one)
                 * 2. We use the default topic
                 */

                //action topic
                if ($traitType->needsActionTopic && array_key_exists('actionTopic', $requestedTrait)) {
                    if (! $this->validateMqttTopic($requestedTrait['actionTopic'])) {
                        $device->delete();

                        return $this->errorResponse(400, 'mqtt_invalid_topic', 'The MQTT topic may only contain alphanumeric characters, slashes, dashes and underscores');
                    }
                    $newTraits[$traitTypeId]['mqttActionTopic'] = $this->removeGbridgePrefixFromMqttTopic($userid, $requestedTrait['actionTopic']);
                } else {
                    $newTraits[$traitTypeId]['mqttActionTopic'] = 'd'.$device->device_id.'/'.strtolower($traitShortname);
                }

                //status topic
                if ($traitType->needsStatusTopic && array_key_exists('statusTopic', $requestedTrait)) {
                    if (! $this->validateMqttTopic($requestedTrait['actionTopic'])) {
                        $device->delete();

                        return $this->errorResponse(400, 'mqtt_invalid_topic', 'The MQTT topic may only contain alphanumeric characters, slashes, dashes and underscores');
                    }
                    $newTraits[$traitTypeId]['mqttStatusTopic'] = $this->removeGbridgePrefixFromMqttTopic($userid, $requestedTrait['statusTopic']);
                } else {
                    $newTraits[$traitTypeId]['mqttStatusTopic'] = 'd'.$device->device_id.'/'.strtolower($traitShortname).'/set';
                }

                //available modes for trait "TempSet.Mode"
                if ($traitType->shortname === 'TempSet.Mode') {
                    if (array_key_exists('modesSupported', $requestedTrait)) {
                        if (! $this->checkIfVariableIsStringArray($requestedTrait['modesSupported'])) {
                            $device->delete();

                            return $this->errorResponse(400, 'generic', 'Malformed data for modesSupported');
                        }
                        foreach ($requestedTrait['modesSupported'] as $requestedMode) {
                            if (! in_array($requestedMode, ['off', 'heat', 'cool', 'on', 'auto', 'fan-only', 'purifier', 'eco', 'dry'])) {
                                $device->delete();

                                return $this->errorResponse(400, 'generic', 'Unknown mode requested in modesSupported');
                            }
                        }

                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'modesSupported' => array_values(array_unique($requestedTrait['modesSupported'])),
                        ]);
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'modesSupported' => ['off', 'heat', 'on', 'auto'],
                        ]);
                    }
                }

                if ($traitType->shortname === 'TempSet.Humidity') {
                    if (array_key_exists('humiditySupported', $requestedTrait)) {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'humiditySupported' => $requestedTrait['humiditySupported'] ? true : false,
                        ]);
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'humiditySupported' => false,
                        ]);
                    }
                }

                if ($traitType->shortname === 'FanSpeed') {
                    if (array_key_exists('fanSpeeds', $requestedTrait)) {
                        $fanSpeeds = $requestedTrait['fanSpeeds'];

                        if (! is_array($fanSpeeds)) {
                            //the object itself has to be an array of objects
                            $device->delete();

                            return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                        }

                        //workaround needed: $availableFanSpeeds as objects, so that even numeric keys get saved as a string
                        $availableFanSpeeds = new \stdClass();
                        foreach ($fanSpeeds as $speedVal => $speedNames) {
                            if (! is_array($speedNames)) {
                                //speed names need to be an array, too
                                $device->delete();

                                return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                            }

                            $speedNamesNormalized = [];
                            foreach ($speedNames as $speedName) {
                                if (! is_string($speedName)) {
                                    $device->delete();

                                    return $this->errorResponse(400, 'generic', 'Malformed fanSpeeds');
                                }
                                $speedNamesNormalized[] = $speedName;
                            }

                            $availableFanSpeeds->{strval($speedVal)} = ['names' => $speedNamesNormalized];
                        }

                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'availableFanSpeeds' => $availableFanSpeeds,
                        ]);
                    } else {
                        $newTraits[$traitTypeId]['config'] = json_encode([
                            'availableFanSpeeds' => ['S1' => ['names' => ['Slow']], 'S2' => ['names' => ['Medium']], 'S3' => ['names' => ['Fast']]],
                        ]);
                    }
                }

                if ($traitType->shortname === 'CameraStream') {
                    $streamFormat = 'progressive_mp4';
                    $streamDefaultUrl = null;

                    if (array_key_exists('streamFormat', $requestedTrait)) {
                        $streamFormat = $requestedTrait['streamFormat'];
                        if (! in_array($streamFormat, ['progressive_mp4', 'hls', 'dash', 'smooth_stream'])) {
                            $device->delete();

                            return $this->errorResponse(400, 'generic', 'Invalid stream format given');
                        }
                    }
                    if (array_key_exists('streamDefaultUrl', $requestedTrait)) {
                        $streamDefaultUrl = $requestedTrait['streamDefaultUrl'];
                    }

                    $newTraits[$traitTypeId]['config'] = json_encode([
                        'cameraStreamFormat' => $streamFormat,
                        'cameraStreamDefaultUrl' => $streamDefaultUrl,
                    ]);
                }
            }
        }

        $device->traits()->sync($newTraits);
        $device->save();

        $this->userInfoToCache(auth('apiv2')->user());

        $device = $device->fresh();

        return response()->json($device->toApiV2Object(auth('apiv2')->user()->user_id), 201);
    }

    public function deleteDeviceById(Request $request, $deviceid)
    {
        $userid = auth('apiv2')->user()->user_id;

        $deviceid = intval($deviceid);

        if ($deviceid === 0) {
            return $this->errorResponse(400, 'device_invalid_id', 'Your provided device id is invalid or malformed');
        }

        $device = auth('apiv2')->user()->devices()->find($deviceid);

        if (! $device) {
            return $this->errorResponse(404, 'device_not_found', 'The device you are asking for does not exist');
        }

        $device->delete();

        $this->userInfoToCache(auth('apiv2')->user());

        return response(null, 204);
    }

    public function requestSynchronization(Request $request)
    {
        $this->userInfoToCache(auth('apiv2')->user());

        $userid = auth('apiv2')->user()->user_id;
        try {
            Redis::publish("gbridge:u$userid:d0:requestsync", '0');
        } catch (Exception $e) {
            return $this->errorResponse(500, 'internal_error', 'An internal error occured');
        }

        return response(null, 200);
    }

    public function getUserDetails(Request $request)
    {
        $user = auth('apiv2')->user();

        if (! auth('apiv2')->payload()->get('privilege_user')) {
            return $this->errorResponse(401, 'insufficient_privileges', 'Your access token has insufficient privileges for this operation');
        }

        return response()->json([
            'userId' => strval($user->user_id),
            'displayName' => $user->name,
            'email' => $user->email,
            'mqttUsername' => 'gbridge-u'.$user->user_id,
        ]);
    }

    public function updateUserPassword(Request $request)
    {
        $user = auth('apiv2')->user();

        if (! auth('apiv2')->payload()->get('privilege_user')) {
            return $this->errorResponse(401, 'insufficient_privileges', 'Your access token has insufficient privileges for this operation');
        }

        $password = strval($request->input('password'));

        if (! preg_match('/^.*(?=.{5,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#"%§&\/()=?+*~#\'\-_<>,;.:^@]).*$/', $password)) {
            return $this->errorResponse(400, 'password_weak', 'Your password does not satisfy the required criteria');
        }

        $user->password = Hash::make($password);
        $user->save();

        return response(null, 200);
    }

    public function updateUserMqttPassword(Request $request)
    {
        $user = auth('apiv2')->user();

        if (! auth('apiv2')->payload()->get('privilege_user')) {
            return $this->errorResponse(401, 'insufficient_privileges', 'Your access token has insufficient privileges for this operation');
        }

        $password = strval($request->input('mqttPassword'));

        if (strlen($password) < 8) {
            return $this->errorResponse(400, 'password_weak', 'Your password does not satisfy the required criteria');
        }

        //MQTT server requires an password string that is unusual, but based on PBKDF2. It must be build manually.
        $salt = Str::random(16);
        $key = base64_encode(hash_pbkdf2('sha256', $password, $salt, 902, 24, true));
        $mqtt_key = "PBKDF2\$sha256\$902\$$salt\$$key";

        $user->mqtt_password = $mqtt_key;
        $user->save();

        return response(null, 200);
    }

    /**
     * Return this function if an error occured
     *
     * @param statuscode HTTP error code, e.g. 404
     * @param errorcode Defined, machine processable error code, e.g. 'invalid_id'
     * @param message Human readable message
     */
    private function errorResponse($statuscode, $errorcode, $message)
    {
        /**
         * Error Codes:
         * generic
         * device_invalid_id
         * device_not_found
         */
        return response()->json([
            'error_code' => $errorcode,
            'error_message' => $message,
        ], $statuscode);
    }
}
