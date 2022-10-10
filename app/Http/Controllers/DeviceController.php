<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\TraitType;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    private $deviceService;

    /**
     * Send a request to the worker, in order to request Google to refresh the customer's device list
     */
    public function googleRequestSync()
    {
        $userid = Auth::user()->user_id;
        Redis::publish("gbridge:u$userid:d0:requestsync", '0');
    }

    /**
     * Stores general information about all user's devices in the redis cache for usage with oder modules of gBridge
     */
    public function allUserInfoToCache()
    {
        foreach (User::all() as $user) {
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

        echo 'SUCCESS';
    }

    /**
     * Force Authentication
     */
    public function __construct(DeviceService $deviceService)
    {
        $this->middleware('auth');
        $this->deviceService = $deviceService;
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
        $traitTypes = $traitTypes->filter(function ($trait, $key) {
            //Only TempSet.Mode is kept, TempSet.* is removed
            return ! in_array($trait->shortname, ['TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity']);
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

        $this->deviceService->create($request, Auth::user());

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

        if (! $device) {
            return redirect()->route('device.index')->with('error', 'Device does not exist');
        }

        $traitTypes = TraitType::all();

        //Special handling for trait "TemperatureSetting". Only one trait (instead of 4) is shown for the user
        $traitTypes = $traitTypes->filter(function ($trait, $key) {
            //Only TempSet.Mode is kept, TempSet.* is removed
            return ! in_array($trait->shortname, ['TempSet.Setpoint', 'TempSet.Ambient', 'TempSet.Humidity']);
        });
        $traitTypes->where('shortname', 'TempSet.Mode')->first()->name = 'Temperature Setting';

        //Parse JSON in the pivot
        foreach ($device->traits as $trait) {
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
    public function update(Request $request, int $id)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
            'twofa_type' => 'bail|nullable|in:none,ack,pin',
            'twofa_pin' => 'bail|required_if:twofa_type,pin|max:16',
        ]);
        $this->deviceService->update($request, $id, Auth::user());

        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device modified');
    }

    /**
     * Update MQTT topics for this device.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Device  $device
     * @param  TraitType  $trait
     * @return \Illuminate\Http\Response
     */
    public function updatetopic(Request $request, Device $device, TraitType $trait)
    {
        $user = Auth::user();
        $device = Auth::user()->devices()->where('device_id', $device->device_id)->first();
        if (! $device) {
            return redirect()->route('device.index')->with('error', 'The device to be updated does not exist.');
        }

        $traittype = $device->traits->where('traittype_id', $trait->traittype_id)->first();

        //build the validator configuration
        $validatorConf = [
            //Action topics are required for these traits: OnOff, Brightness, Scene, TempSet.Mode, TempSet.Setpoint
            'action' => 'bail|required|regex:/^[a-z0-9-_\/]+$/i',
            //Status topics are required for the traits: OnOff, Brightness, TempSet.Mode, TempSet.Setpoint, TempSet.Ambient, TempSet.Humidity
            'status' => 'bail|required|regex:/^[a-z0-9-_\/]+$/i',
        ];

        //Special handling for trait TempSet.Mode
        if ($traittype->shortname == 'TempSet.Mode') {
            $validatorConf['modes'] = 'bail|required|array';
            $validatorConf['modes.*'] = 'bail|required|in:off,heat,cool,on,auto,fan-only,purifier,eco,dry';
        }

        //Special handling for trait FanSpeed -> requiring speed settings
        if ($traittype->shortname == 'FanSpeed') {
            $validatorConf['fanSpeeds'] = 'bail|required|min:1';
        }

        //Special handling for trait CameraStream -> requiring media type and possible default uri
        if ($traittype->shortname == 'CameraStream') {
            $validatorConf['streamFormat'] = 'bail|required|in:hls,progressive_mp4,dash,smooth_stream';
            $validatorConf['streamDefaultUrl'] = 'nullable|max:250';
        }

        $this->validate($request, $validatorConf, [
            'required' => 'Please specify the required settings!',
            'regex' => 'The topics may only contain alphanumeric chracters, slashes (/), underscores (_) and dashes (-)!',
            'in' => 'You\'ve specified an invalid mode/ value/ format!',
        ]);

        //Special handling (config parse) for trait TempSet.Humidity
        if ($traittype->shortname == 'TempSet.Humidity') {
            $traittype->pivot->config = json_encode([
                'humiditySupported' => $request->input('humiditySupported') ? true : false,
            ]);
        }

        //Special handling (config parse) for trait TempSet.Mode
        if ($traittype->shortname == 'TempSet.Mode') {
            $traittype->pivot->config = json_encode([
                'modesSupported' => $request->input('modes'),
            ]);
        }

        //Special handling (config parse) for trait FanSpeed
        if ($traittype->shortname == 'FanSpeed') {
            if (! $traittype->setAvailableFanSpeedsFromString(explode("\n", $request->input('fanSpeeds')))) {
                return redirect()->back()->with('error', 'Malformed fan speed data');
            }
        }

        //Special handling (config parse) for trait CameraStraem
        if ($traittype->shortname == 'CameraStream') {
            $traittype->setCameraStreamConfig($request->input('streamFormat'), $request->input('streamDefaultUrl'));
        }

        //Sync the trait config for this device
        $traittype->pivot->mqttActionTopic = $request->input('action');
        $traittype->pivot->mqttStatusTopic = $request->input('status');
        $traittype->pivot->save();

        $this->deviceService->userInfoToCache($user);

        //if this trait contains a custom config, it is quite likely, that Google needs to synchronize this conf
        if (! empty($traittype->pivot->config)) {
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
        $this->deviceService->delete($id, Auth::user());

        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device deleted');
    }
}
