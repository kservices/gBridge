<?php

namespace App\Http\Controllers;

use App\DeviceType;
use App\Services\DeviceService;
use App\TraitType;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class DeviceController extends Controller
{
    private $deviceService;

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
    public function __construct(DeviceService $deviceService){
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

        $this -> deviceService -> create($request, Auth::user() -> user_id);

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
    public function update(Request $request, int $id)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
        ]);
        $this -> deviceService -> update($request, $id, Auth::user());
        
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
        $this -> deviceService -> delete($id, Auth::user());

        $this->userInfoToCache();
        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device deleted');
    }
}
