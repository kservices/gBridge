<?php

namespace App\Http\Controllers;

use App\Accesskey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AccesskeyController extends Controller
{
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
        $accesskeys = Auth::user()->accesskeys()->orderBy('generated_at', 'asc')->get();

        //determine the status of each accesskey
        foreach($accesskeys as $accesskey){
            if($accesskey->password_used){
                $accesskey->status = 'USED';
            }elseif(Carbon::now('Europe/Berlin') > Carbon::parse($accesskey->generated_at, 'Europe/Berlin')->addHours(1)){
                $accesskey->status = 'EXPIRED';
            }else{
                $accesskey->status = 'READY';
            }

            $accesskey->valid_until = Carbon::parse($accesskey->generated_at, 'Europe/Berlin')->addHours(1);
        }

        return view('accesskey.accesskeys', [
            'site_title' => 'Accesskeys',
            'accesskeys' => $accesskeys,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accesskey = new Accesskey;
        $accesskey->password = str_random(8);
        $accesskey->google_key = password_hash(str_random(32), PASSWORD_BCRYPT);
        $accesskey->user_id = Auth::user()->user_id;

        $accesskey->save();

        return redirect()->route('accesskey.index')->with('success', 'Created new Accesskey');
    }

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
