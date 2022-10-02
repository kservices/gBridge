<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Models\TraitType;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    private $deviceService;

    /**
     * Force Authentication
     */
    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
        $this->middleware('auth.basic');
    }

    public function getDevices(Request $request)
    {
        return json_encode($request->user()->devices()->get());
    }

    public function getTraitTypes()
    {
        return json_encode(TraitType::all());
    }

    public function getDeviceTypes()
    {
        return json_encode(DeviceType::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createDevice(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
        ]);
        $this->deviceService->create($request, Auth::user());

        return response('Created', 201);
    }

    /**
     * Updates a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateDevice(Request $request, int $id)
    {
        $this->validate($request, [
            'name' => 'bail|required|max:32|min:1',
            'type' => 'bail|required|numeric|exists:device_type,devicetype_id',
            'traits' => 'bail|required|array',
            'traits.*' => 'bail|required|numeric|exists:trait_type,traittype_id',
        ]);
        $this->deviceService->update($request, $id, Auth::user());

        return response('Updated', 200);
    }

    /**
     * Deletes a resource in storage.
     *
     * @param  int  $id
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function deleteDevice(int $id)
    {
        $this->deviceService->delete($id, Auth::user());

        return response('Deleted', 200);
    }
}
