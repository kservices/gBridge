<?php

namespace App\Services;

use App\Models\Device;
use App\Models\TraitType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DeviceService
{
    public function create(Request $request, User $user)
    {
        $device = new Device;
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = $user->user_id;
        $device->save();

        $requestTraits = $request->input('traits');

        //Special handling for trait "TemperatureSetting": If the user selected TempSet.Mode, add the other three belonging traits
        $allTraits = TraitType::all();
        if (in_array($allTraits->where('shortname', 'TempSet.Mode')->first()->traittype_id, $requestTraits)) {
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Setpoint')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Ambient')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Humidity')->first()->traittype_id;
        }

        $traits = [];
        $topicPrefixFromRequest = $request->input('topicPrefix');
        $topicPrefix = $topicPrefixFromRequest ? $topicPrefixFromRequest : 'd'.$device->device_id;
        //use the default MQTT topics for the traits
        foreach ($requestTraits as $traitTypeId) {
            $traitType = TraitType::find($traitTypeId);

            //some trait short names contain a . (dot) -> composite traits
            //replace them with a dash
            $traitShortname = str_replace('.', '-', $traitType->shortname);

            $traits[$traitTypeId] = [
                'mqttActionTopic' => $topicPrefix.'/'.strtolower($traitShortname),
                'mqttStatusTopic' => $topicPrefix.'/'.strtolower($traitShortname).'/set',
            ];

            //special handling for trait TempSet.Mode: Define default supported modes
            if ($traitType->shortname == 'TempSet.Mode') {
                $traits[$traitTypeId]['config'] = json_encode([
                    'modesSupported' => ['off', 'heat', 'on', 'auto'],
                ]);
            }

            //special handling for trait FanSpeed: Define default speeds
            if ($traitType->shortname == 'FanSpeed') {
                $traits[$traitTypeId]['config'] = json_encode([
                    'availableFanSpeeds' => ['S1' => ['names' => ['Slow']], 'S2' => ['names' => ['Medium']], 'S3' => ['names' => ['Fast']]],
                ]);
            }
        }
        $device->traits()->sync($traits);

        $this->userInfoToCache($user);
    }

    public function update(Request $request, int $id, User $user)
    {
        $device = $user->devices()->find($id);
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        if (is_null($request->input('twofa_type')) || ($request->input('twofa_type') == 'none')) {
            $device->twofa_type = null;
        } else {
            $device->twofa_type = $request->input('twofa_type');
            if ($request->input('twofa_type') == 'pin') {
                $device->twofa_pin = $request->input('twofa_pin');
            } else {
                $device->twofa_pin = null;
            }
        }
        $device->user_id = $user->user_id;

        $device->save();

        $requestTraits = $request->input('traits');

        //Special handling for trait "TemperatureSetting": If the user selected TempSet.Mode, add the other three belonging traits
        $allTraits = TraitType::all();
        if (in_array($allTraits->where('shortname', 'TempSet.Mode')->first()->traittype_id, $requestTraits)) {
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Setpoint')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Ambient')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Humidity')->first()->traittype_id;
        }

        $traits = [];
        //use the default MQTT topics for the traits if the trait is added newly,
        //or use the previous one
        foreach ($requestTraits as $traitTypeId) {
            $traitType = TraitType::find($traitTypeId);

            if ($device->traits->where('traittype_id', $traitTypeId)->count()) {
                //trait has been specified before

                $traits[$traitTypeId] = [
                    'mqttActionTopic' => $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttActionTopic,
                    'mqttStatusTopic' => $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttStatusTopic,
                ];
            } else {
                //trait was newly added

                //some trait short names contain a . (dot) -> composite traits
                //replace them with a dash
                $traitShortname = str_replace('.', '-', $traitType->shortname);

                $traits[$traitTypeId] = [
                    'mqttActionTopic' => 'd'.$device->device_id.'/'.strtolower($traitShortname),
                    'mqttStatusTopic' => 'd'.$device->device_id.'/'.strtolower($traitShortname).'/set',
                ];
            }
        }
        $device->traits()->sync($traits);
        $this->userInfoToCache($user);
    }

    public function delete($id, User $user)
    {
        $device = $user->devices()->find($id);
        if ($device) {
            return $device->delete();
        }
        $this->userInfoToCache($user);

        return false;
    }

    /**
     * Store general information about the user in the redis cache for usage with other modules of gBridge
     *
     * @param  User  $user
     * TODO: Device cache should be updated in background
    */
    public function userInfoToCache(User $user)
    {
        $deviceinfo = [];
        foreach ($user->devices as $device) {
            $deviceinfo[$device->device_id] = [];
            foreach ($device->traits as $trait) {
                $deviceinfo[$device->device_id][$trait->shortname] = [
                    'actionTopic' => $trait->pivot->mqttActionTopic,
                    'statusTopic' => $trait->pivot->mqttStatusTopic,
                    'statusPayloadType' => $trait->pivot->mqttStatusPayloadType,
                    'statusPayloadKey' => $trait->pivot->mqttStatusPayloadKey,
                ];
            }
        }
        $userid = $user->user_id;
        Redis::set("gbridge:u$userid:devices", json_encode($deviceinfo));
    }
}
