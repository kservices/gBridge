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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

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
        $traitTypes = TraitType::all();

        //Special handling for trait "TemperatureSetting". Only one trait (instead of 4) is shown for the user
        $traitTypes = $traitTypes->filter(function($trait, $key){
            //Only TempSet.Mode is kept, TempSet.* is removed
            return !in_array($trait->shortname, ['TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity']);
        });
        $traitTypes->where('shortname', 'TempSet.Mode')->first()->name = 'Temperature Setting';

        return view('device.create', [
            'site_title' => 'New Device',
            'devicetypes' => DeviceType::all(),
            'traittypes' => $traitTypes,
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


        $requestTraits = $request->input('traits');

        //Special handling for trait "TemperatureSetting": If the user selected TempSet.Mode, add the other three belonging traits
        $allTraits = TraitType::all();
        if(in_array( $allTraits->where('shortname', 'TempSet.Mode')->first()->traittype_id, $requestTraits )){
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Setpoint')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Ambient')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Humidity')->first()->traittype_id;
        }

        $traits = [];
        //use the default MQTT topics for the traits
        foreach($requestTraits as $traitTypeId){
            $traitType = TraitType::find($traitTypeId);

            //some trait short names contain a . (dot) -> composite traits
            //replace them with a dash
            $traitShortname = str_replace('.', '-', $traitType->shortname);

            $traits[$traitTypeId] = [
                'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitShortname),
                'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitShortname) . '/set'
            ];

            //special handling for trait TempSet.Mode: Define default supported modes
            if($traitType->shortname == 'TempSet.Mode'){
                $traits[$traitTypeId]['config'] = json_encode([
                    'modesSupported' => ['off', 'heat', 'on', 'auto']
                ]);
            }
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

        $traitTypes = TraitType::all();

        //Special handling for trait "TemperatureSetting". Only one trait (instead of 4) is shown for the user
        $traitTypes = $traitTypes->filter(function($trait, $key){
            //Only TempSet.Mode is kept, TempSet.* is removed
            return !in_array($trait->shortname, ['TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity']);
        });
        $traitTypes->where('shortname', 'TempSet.Mode')->first()->name = 'Temperature Setting';

        //Parse JSON in the pivot
        foreach($device->traits as $trait){
            $conf = $trait->pivot->config;
            $trait->pivot->config = Collection::make(json_decode($conf, true));
        }

        return view('device.edit', [
            'site_title' => 'Edit Device',
            'device' => $device,
            'devicetypes' => DeviceType::all(),
            'traittypes' => $traitTypes,
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


        $requestTraits = $request->input('traits');

        //Special handling for trait "TemperatureSetting": If the user selected TempSet.Mode, add the other three belonging traits
        $allTraits = TraitType::all();
        if(in_array( $allTraits->where('shortname', 'TempSet.Mode')->first()->traittype_id, $requestTraits )){
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Setpoint')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Ambient')->first()->traittype_id;
            $requestTraits[] = $allTraits->where('shortname', 'TempSet.Humidity')->first()->traittype_id;
        }

        $traits = [];
        //use the default MQTT topics for the traits if the trait is added newly,
        //or use the previous one
        foreach($requestTraits as $traitTypeId){
            $traitType = TraitType::find($traitTypeId);

            if($device->traits->where('traittype_id', $traitTypeId)->count()){
                //trait has been specified before
                
                $traits[$traitTypeId] = [
                    'mqttActionTopic' => $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttActionTopic,
                    'mqttStatusTopic' => $device->traits->where('traittype_id', $traitTypeId)->first()->pivot->mqttStatusTopic
                ];
            }else{
                //trait was newly added

                //some trait short names contain a . (dot) -> composite traits
                //replace them with a dash
                $traitShortname = str_replace('.', '-', $traitType->shortname);

                $traits[$traitTypeId] = [
                    'mqttActionTopic' => 'd' . $device->device_id . '/' . strtolower($traitShortname),
                    'mqttStatusTopic' => 'd' . $device->device_id . '/' . strtolower($traitShortname) . '/set'
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
     * @param  Device $device
     * @param   TraitType $trait
     * @return \Illuminate\Http\Response
     */
    public function updatetopic(Request $request, Device $device, TraitType $trait)
    {
        $traittype = $device->traits->where('traittype_id', $trait->traittype_id)->first();

        //build the validator configuration
        $validatorConf = [
            //Action topics are required for these traits: OnOff, Brightness, Scene, TempSet.Mode, TempSet.Setpoint
            'action' => 'bail|required|regex:/^[a-z0-9-_\/]+$/i',
            //Status topics are required for the traits: OnOff, Brightness, TempSet.Mode, TempSet.Setpoint, TempSet.Ambient, TempSet.Humidity
            'status' => 'bail|required|regex:/^[a-z0-9-_\/]+$/i'
        ];

        //Special handling for trait TempSet.Mode
        if($traittype->shortname == 'TempSet.Mode'){
            $validatorConf['modes'] = 'bail|required|array';
            $validatorConf['modes.*'] = 'bail|required|in:off,heat,cool,on,auto,fan-only,purifier,eco,dry';
        }

        $this->validate($request, $validatorConf, [
            'required' => 'Please specify the required settings!',
            'regex' => 'The topics may only contain alphanumeric chracters, slashes (/), underscores (_) and dashes (-)!',
            'in' => 'You\'ve specified an invalid thermostat mode!'
        ]);

        //Special handling (config parse) for trait TempSet.Humidity
        if($traittype->shortname == 'TempSet.Humidity'){
            $traittype->pivot->config = json_encode([
                'humiditySupported' => $request->input('humiditySupported') ? true:false
            ]);
        }

        //Special handling (config parse) for trait TempSet.Mode
        if($traittype->shortname == 'TempSet.Mode'){
            $traittype->pivot->config = json_encode([
                'modesSupported' => $request->input('modes')
            ]);
        }

        //Sync the trait config for this device
        $traittype->pivot->mqttActionTopic = $request->input('action');
        $traittype->pivot->mqttStatusTopic = $request->input('status');
        $traittype->pivot->save();
        
        $this->userInfoToCache();

        //if this trait contains a custom config, it is quite likely, that Google needs to synchronize this conf
        if(!empty($traittype->pivot->config)){
            $this->googleRequestSync();
        }

        return redirect()->route('device.index')->with('success', 'Device settings modified');
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
