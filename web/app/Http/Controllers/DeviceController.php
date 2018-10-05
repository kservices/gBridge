<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\User;
use App\Device;
use App\DeviceType;
use App\TraitType;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * Send a request to the worker, in order to request Google to refresh the customer's device list
     */
    public function googleRequestSync(){
        $userid = Auth::user()->user_id;
        Redis::publish("gbridge:u$userid:d0:requestsync", "0");
    }

    /**
     * Store general information about the user in the redis cache for usage with other modules of gBridge
     */
    public function userInfoToCache(){
        $deviceinfo = [];
        foreach(Auth::user()->devices as $device){
            $deviceinfo[$device->device_id] = [];
            foreach($device->traits as $trait){
                $deviceinfo[$device->device_id][$trait->shortname] = [
                    'actionTopic' => $trait->pivot->mqttActionTopic,
                    'statusTopic' => $trait->pivot->mqttStatusTopic
                ];
            }
        }
        $userid = Auth::user()->user_id;
        Redis::set("gbridge:u$userid:devices", json_encode($deviceinfo));
    }

    /**
     * Stores general information about all user's devices in the redis cache for usage with oder modules of gBridge
     */
    public function allUserInfoToCache(){
        foreach(User::all() as $user){
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

        print("SUCCESS");
    }

    /**
     * Force Authentication
     */
    public function __construct(){
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $devices = Auth::user()->devices()->orderBy('name', 'asc')->get();

        //check user with if(Auth::user()) -> null is returned, if not logged in

        return view('device.devices', [
            'site_title' => 'All Devices',
            'devices' => $devices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('device.create', [
            'site_title' => 'New Device',
            'devicetypes' => DeviceType::all(),
            'traittypes' => TraitType::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
        ]);

        $device = new Device;
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = Auth::user()->user_id;

        $device->save();

        $traits = [];
        //use the default MQTT topics for the traits
        foreach($request->input('traits') as $traitTypeId){
            $traitType = TraitType::find($traitTypeId);

            $traits[$traitTypeId] = [
                'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname),
                'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitType->shortname) . '/set'
            ];
        }
        $device->traits()->sync($traits);
        
        $this->userInfoToCache();
        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device added');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $device = Auth::user()->devices()->find($id);

        if(!$device){
            return redirect()->route('device.index')->with('error', 'Device does not exist');
        }

        return view('device.edit', [
            'site_title' => 'Edit Device',
            'device' => $device,
            'devicetypes' => DeviceType::all(),
            'traittypes' => TraitType::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
        ]);

        $device = Auth::user()->devices()->find($id);
        $device->name = $request->input('name');
        $device->devicetype_id = $request->input('type');
        $device->user_id = Auth::user()->user_id;

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
        
        $this->userInfoToCache();
        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device modified');
    }

    /**
     * Update MQTT topics for this device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatetopic(Request $request, $id)
    {
        $device = Auth::user()->devices()->find($id);

        //build the validator configuration
        $validatorConf = [];
        foreach($device->traits as $trait){
            $validatorConf[$trait->traittype_id . '-action'] = 'bail|required|regex:/^[a-z0-9-_\/]+$/i';
            $validatorConf[$trait->traittype_id . '-status'] = 'bail|required|regex:/^[a-z0-9-_\/]+$/i';
        }

        $this->validate($request, $validatorConf, [
            'required' => 'Please specify a topic for each trait!',
            'regex' => 'The topics may only contain alphanumeric chracters, slashes (/), underscores (_) and dashes (-)!'
        ]);

        //Sync the trait config for this device
        $traits = [];
        foreach($device->traits as $trait){
            $traitTypeId = $trait->traittype_id;

            $traits[$traitTypeId] = [
                'mqttActionTopic' => $request->input($traitTypeId . '-action'),
                'mqttStatusTopic' => $request->input($traitTypeId . '-status')
            ];
        }
        $device->traits()->sync($traits);
        
        $this->userInfoToCache();

        //Not necessary here?!
        //$this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device topics modified');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $device = Auth::user()->devices()->find($id);
        $device->delete();

        $this->userInfoToCache();
        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device deleted');
    }
}
