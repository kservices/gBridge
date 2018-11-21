<?php
namespace App\Services;

use App\Device;
use App\TraitType;
use App\User;
use Illuminate\Http\Request;

class DeviceService
{
    public function create(Request $request, String $userId)
    {
        $device = new Device;
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = $userId;

        $device->save();

        $traits = [];
        foreach($request->input('traits') as $traitTypeId){
            $traitType = TraitType::find($traitTypeId);

            $traits[$traitTypeId] = [
                'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname),
                'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname) . '/set'
            ];
        }
        $device->traits()->sync($traits);
    }
    
    public function update(Request $request, int $id, User $user){
        $device = $user->devices()->find($id);
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = $user->user_id;

        $device->save();

        $traits = [];
        //use the default MQTT topics for the traits if the trait is added newly,
        //or use the previous one
        foreach($request->input('traits') as $traitTypeId){
            $traitType = TraitType::find($traitTypeId);

            if($device->traits->where('traittype_id', $traitTypeId)->count()){
                //trait has been specified before
                $traits[$traitTypeId] = [
                    'mqttActionTopic' => $device->traits->where('traittype_id', $traitTypeId)[0]->pivot->mqttActionTopic,
                    'mqttStatusTopic' => $device->traits->where('traittype_id', $traitTypeId)[0]->pivot->mqttStatusTopic
                ];
            }else{
                //trait was newly added
                $traits[$traitTypeId] = [
                    'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname),
                    'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname) . '/set'
                ];
            }
        }
        $device->traits()->sync($traits);
    }

    public function delete($id, User $user){
        $device = $user->devices()->find($id);
        return $device->delete();
    }
}