<?php

namespace App\Services;

use App\Device;
use App\TraitType;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DeviceService
{
    public function create(Request $request, User $user)
    {
        $device = new Device;
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = $user -> user_id;
        $device->save();

        $traits = [];
        $topicPrefixFromRequest = $request->input('topicPrefix');
        $topicPrefix = $topicPrefixFromRequest ? $topicPrefixFromRequest : 'd' . $device->device_id;
        foreach ($request->input('traits') as $traitTypeId) {
            $traitType = TraitType::find($traitTypeId);

            $traits[$traitTypeId] = [
                'mqttActionTopic' => $topicPrefix . '/' . strtolower($traitType->shortname),
                'mqttStatusTopic' => $topicPrefix . '/' . strtolower($traitType->shortname) . '/set'
            ];
        }
        $device->traits()->sync($traits);
        $this -> userInfoToCache($user);
    }

    public function update(Request $request, int $id, User $user)
    {
        $device = $user->devices()->find($id);
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = $user->user_id;

        $device->save();

        $traits = [];
        //use the default MQTT topics for the traits if the trait is added newly,
        //or use the previous one
        foreach ($request->input('traits') as $traitTypeId) {
            $traitType = TraitType::find($traitTypeId);

            if ($device->traits->where('traittype_id', $traitTypeId)->count()) {
                //trait has been specified before
                $traits[$traitTypeId] = [
                    'mqttActionTopic' => $device->traits->where('traittype_id', $traitTypeId)[0]->pivot->mqttActionTopic,
                    'mqttStatusTopic' => $device->traits->where('traittype_id', $traitTypeId)[0]->pivot->mqttStatusTopic
                ];
            } else {
                //trait was newly added
                $traits[$traitTypeId] = [
                    'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname),
                    'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname) . '/set'
                ];
            }
        }
        $device->traits()->sync($traits);
        $this -> userInfoToCache($user);
    }

    public function delete($id, User $user)
    {
        $device = $user->devices()->find($id);
        if($device){
            return $device->delete();
        }
        $this -> userInfoToCache($user);
        return false;
    }

    /**
     * Store general information about the user in the redis cache for usage with other modules of gBridge
     * @param User $user
     */
    public function userInfoToCache(User $user){
        $deviceinfo = [];
        foreach($user->devices as $device){
            $deviceinfo[$device->device_id] = [];
            foreach($device->traits as $trait){
                $deviceinfo[$device->device_id][$trait->shortname] = [
                    'actionTopic' => $trait->pivot->mqttActionTopic,
                    'statusTopic' => $trait->pivot->mqttStatusTopic
                ];
            }
        }
        $userid = $user->user_id;
        Redis::set("gbridge:u$userid:devices", json_encode($deviceinfo));
    }
}