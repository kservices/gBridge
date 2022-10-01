<?php

namespace App\Http\Controllers;

use App\Accesskey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccesskeyController extends Controller
{
    /**
     * Force Authentication
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accesskeys = Auth::user()->accesskeys()->orderBy('generated_at', 'asc')->get();

        return view('accesskey.accesskeys', [
            'site_title' => 'Account Linking',
            'accesskeys' => $accesskeys,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    //public function create()
    //{
    //
    //}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //public function store(Request $request)
    //{
    //
    //}

    /**
     * Display the specified resource.
     *
     * @param  \App\Accesskey  $accesskey
     * @return \Illuminate\Http\Response
     */
    //public function show(Accesskey $accesskey)
    //{
    //
    //}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Accesskey  $accesskey
     * @return \Illuminate\Http\Response
     */
    //public function edit(Accesskey $accesskey)
    //{
    //
    //}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Accesskey  $accesskey
     * @return \Illuminate\Http\Response
     */
    //public function update(Request $request, Accesskey $accesskey)
    //{
    //
    //}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Accesskey  $accesskey
     * @return \Illuminate\Http\Response
     */
    public function destroy(Accesskey $accesskey)
    {
        $accesskey->delete();

        return redirect()->route('accesskey.index')->with('success', 'Accesskey deleted');
    }
}
