<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\ApiKey;

class ApiKeyController extends Controller
{
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
        $apikeys = Auth::user()->apiKeys()->orderBy('created_at', 'asc')->get();

        return view('apikey.apikey', [
            'site_title' => 'API Keys',
            'apikeys' => $apikeys,
        ]);
    }

    private function createNewApiKey($hasPrivilegeUser){
        $apikey = new ApiKey();
        $apikey->privilege_user = $hasPrivilegeUser ? true:false;
        
        $apikey->user()->associate(Auth::user());
        $apikey->save();
        
        return redirect()->route('apikey.index')->with('success', 'A new API Key has been created. Store it safely! You need to note it right now since it won\'t be shown anymore after a page reload.')->with('currentApiKey', $apikey->identifier . ':' . $apikey->secret_key); 
    }

    /**
     * Create a new API key with only standard privileges
     */
    public function createStandardKey(Request $request){
        return $this->createNewApiKey(false);    
    }

    /**
     * Create a new API key with both standard and user management privileges
     */
    public function createUserKey(Request $request){
        return $this->createNewApiKey(true);    
    }

    /**
     * Delete this api key.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $apikey_id)
    {
        $apikey = Auth::user()->apiKeys()->find($apikey_id);

        if(!$apikey){
            return redirect()->route('apikey.index')->with('error', 'API Key not found');
        }

        $apikey->delete();
        return redirect()->route('apikey.index')->with('success', 'API Key deleted');
    }
}
