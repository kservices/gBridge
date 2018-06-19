<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Device;
use App\DeviceType;
use App\TraitType;

class DeviceController extends Controller
{
    /**
     * Send a request to Google, in order to refresh the customer's device list
     * @todo this shouldn't be done here, since it is slow...
     */
    public function googleRequestSync(){
        if(env('HOMEGRAPH_APIKEY', null)){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://homegraph.googleapis.com/v1/devices:requestSync?key=' . env('HOMEGRAPH_APIKEY', ''));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{agent_user_id: "' . Auth::user()->user_id . '"}');
            curl_setopt($ch, CURLOPT_POST, 1);
            $headers = array();
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_exec($ch);
            curl_close($ch);
        }else{
            error_log('Please specify your Google Homegraph API-Key in .env!');
        }
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

        $device->traits()->sync($request->input('traits'));
        
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

        $device->traits()->sync($request->input('traits'));
        
        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device modified');
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

        $this->googleRequestSync();

        return redirect()->route('device.index')->with('success', 'Device deleted');
    }
}
