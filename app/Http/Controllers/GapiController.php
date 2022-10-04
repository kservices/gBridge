<?php

namespace App\Http\Controllers;

use App\Models\Accesskey;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\CarbonImmutable;

class GapiController extends Controller
{
    /**
     * Show a form for the user to log in with his accesskey.
     *
     * @return \Illuminate\Http\Response
     */
    public function auth(Request $request)
    {
        return view('gapi.auth', [
            'site_title' => 'Authenticate',
            //those are GET-Parameters supplied by google
            'client_id' => $request->input('client_id', ''),
            'response_type' => $request->input('response_type', ''),
            'redirect_uri' => $request->input('redirect_uri', ''),
            'state' => $request->input('state', ''),
        ]);
    }

    /**
     * Check authentication.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkauth(Request $request)
    {
        if (! Auth::check()) {
            $this->validate($request, [
                'email' => 'bail|required|string|max:255',
                'password' => 'bail|required|string',
            ]);
        }

        //check parameters provided by Google
        $googleerror_prefix = 'The request by Google Home was malformed. Please try again in a few minute. If this problem persists, please contact the team of Kappelt gBridge. ';

        if ($request->input('client_id', '__y') != env('GOOGLE_CLIENT_ID', '__z')) {
            return redirect()->back()->withInput()->with('error', $googleerror_prefix.'Invalid Client ID has been provided!');
        }
        if ($request->input('response_type', '') != 'code') {
            return redirect()->back()->withInput()->with('error', $googleerror_prefix.'Unkown Response Type requested!');
        }
        if ($request->input('redirect_uri', '__x') != ('https://oauth-redirect.googleusercontent.com/r/'.env('GOOGLE_PROJECTID', ''))) {
            return redirect()->back()->withInput()->with('error', $googleerror_prefix.'Invalid redirect Request!');
        }
        if (! $request->input('state')) {
            return redirect()->back()->withInput()->with('error', $googleerror_prefix.'No State given!');
        }

        if (! Auth::check()) {
            //User is not authenticated in this browser. Try logging
            if (! Auth::once(['email' => $request->input('email', ''), 'password' => $request->input('password', '')])) {
                //Try logging in with a username then (for accounts created by resellers)
                if (! Auth::once(['login_username' => $request->input('email', ''), 'password' => $request->input('password', '')])) {
                    return redirect()->back()->withInput()->with('error', "Your account doesn't exist or the credentials don't match our records!");
                }
            }
        }

        if (isset(Auth::user()->verify_token)) {
            return redirect()->back()->withInput()->with('error', "You need to confirm your account. We have sent you an activation code, please check your email account. Check the spam folder for this mail, contact support under gbridge@kappelt.net if you haven't received the confirmation");
        }



        $issuedAt   = CarbonImmutable::now();
        $expire     = $issuedAt->addMinute(6)->getTimestamp();      // Add 60 seconds
        $serverName = "ochui.dev";


        // dd(now());

        $data = [
            'iat' =>  $issuedAt->getTimestamp(),        // Issued at: time when the token was generated
            'iss'  => $serverName,                       // Issuer
            // 'nbf'  => $expire,         // Not before
            'exp'  => $expire,                           // Expire
            'user_id' => Auth::user()->user_id,                     // User name
        ];


        // dd($data);

        $access_code = JWT::encode($data, env('JWT_SECRET'), 'HS512');
        

        return redirect($request->input('redirect_uri').'?code='.$access_code.'&state='.$request->input('state'));
    }



    /**
     * 
     * Handle code exchange :TODO Use laravel Passport
     * @return  \Illuminate\Http\Response
     */

     public function OauthToken(Request $request)
     {
        //check parameters provided by Google
        $googleerror_prefix = ' ';

        if ($request->input('client_id', '__y') != env('GOOGLE_CLIENT_ID', '__z')) {
            Log::info('Invalid Client ID has been provided!');
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $googleerror_prefix.'Invalid Client ID has been provided!'
            ], 400);
        }

        if ($request->input('client_secret', '__y') != env('GOOGLE_CLIENT_SECRET', '__z')) {
            Log::info('Client Secret is invalid!');
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $googleerror_prefix.'Client Authentication failed'
            ], 400);
        }

        if (!in_array($request->input('grant_type', ''), ['authorization_code', 'refresh_token'])) {
            Log::info('Invalid grant type!');
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $googleerror_prefix.'Unknown Response Type requested!'
            ], 400);

        }

        if ($request->input('redirect_uri', '__x') != ('https://oauth-redirect.googleusercontent.com/r/'.env('GOOGLE_PROJECTID', ''))) {
            Log::info('Invalid redirect Request!');
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $googleerror_prefix.'Invalid redirect Request!'
            ], 400);
        }

        if (! $request->input('code')) {
            Log::info('No code given!');
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $googleerror_prefix.'Invalid access code'
            ], 400);
        }


        try {

            $decoded = JWT::decode($request->input('code'), new Key(env('JWT_SECRET'), 'HS512'));
            $accesskey = new Accesskey;
            $accesskey->google_key = password_hash(Str::random(32), PASSWORD_BCRYPT);
            $accesskey->user_id = $decoded->user_id;

            $accesskey->save();

        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'error' => 'Invalid grant',
                'message' => $e->getMessage()
            ], 400);
        }


        return response()->json([
            "token_type" => "Bearer",
            "access_token" => $accesskey->google_key,
            "refresh_token" => $accesskey->google_key,
            "expires_in" => CarbonImmutable::now()->addYear()->getTimestamp()
        ]);

     }

    /**
     * Handle an apicall by google
     *
     * @return \Illuminate\Http\Response
     */
    public function apicall(Request $laravel_request)
    {
        $request = json_decode($laravel_request->getContent(), true);

        Log::info($request);

        //check, whether requestId is present
        if (! isset($request['requestId'])) {
            return $this->errorResponse('', ErrorCode::protocolError, true);
        }
        $requestid = $request['requestId'];

        $accesskey = $laravel_request->header('Authorization', '');
        $accesskey = Accesskey::where('google_key', str_replace('Bearer ', '', $accesskey))->get();

        if (count($accesskey) < 1) {
            return $this->errorResponse($requestid, ErrorCode::authFailure);
        }
        $accesskey = $accesskey[0];
        $user = $accesskey->user;

        //See https://developers.google.com/actions/smarthome/create-app for information about the JSON request format
        if (! isset($request['inputs'])) {
            return $this->errorResponse($requestid, ErrorCode::protocolError);
        }

        $input = $request['inputs'][0];

        if (! isset($input['intent'])) {
            error_log('Intent is undefined!');

            return $this->errorResponse($requestid, ErrorCode::protocolError);
        }

        Redis::hset('gbridge:u'.$user->user_id.':d0', 'grequestid', $requestid);
        Log::info('gbridge:u'.$user->user_id.':d0');

        //Check for users device limit
        if ($user->devices()->count() > $user->device_limit) {
            return $this->errorResponse($requestid, ErrorCode::deviceTurnedOff);
        }

        if ($input['intent'] === 'action.devices.SYNC') {
            //sync-intent
            Redis::hset('gbridge:u'.$user->user_id.':d0', 'grequesttype', 'SYNC');
            Redis::publish('gbridge:u'.$user->user_id.':d0:grequest', 'SYNC');

            return $this->handleSync($user, $requestid);
        } elseif ($input['intent'] === 'action.devices.QUERY') {
            //query-intent
            Redis::hset('gbridge:u'.$user->user_id.':d0', 'grequesttype', 'QUERY');
            Redis::publish('gbridge:u'.$user->user_id.':d0:grequest', 'QUERY');

            return $this->handleQuery($user, $requestid, $input);
        } elseif ($input['intent'] === 'action.devices.EXECUTE') {
            //execute-intent
            Redis::hset('gbridge:u'.$user->user_id.':d0', 'grequesttype', 'EXECUTE');
            Redis::publish('gbridge:u'.$user->user_id.':d0:grequest', 'EXECUTE');

            return $this->handleExecute($user, $requestid, $input);
        } elseif ($input['intent'] === 'action.devices.DISCONNECT') {
            Redis::hset('gbridge:u'.$user->user_id.':d0', 'grequesttype', 'DISCONNECT');
            Redis::publish('gbridge:u'.$user->user_id.':d0:grequest', 'DISCONNECT');
            $accesskey->delete();

            return response()->json([]);
        } else {
            //unknown intent
            error_log('Unknown intent: "'.$input['intent'].'"');

            return $this->errorResponse($requestid, ErrorCode::protocolError);
        }
    }

    /**
     * Handle the Sync-Intent
     *
     * @param user User object.
     * @param requestid The request id
     */
    private function handleSync($user, $requestid)
    {
        $response = [
            'requestId' => $requestid,
            'payload' => [
                'devices' => [],
                'agentUserId' => $user->user_id,            //the agentUserId is here the user_id
            ],
        ];

        $devices = $user->devices;

        foreach ($devices as $device) {
            $trait_googlenames = $device->traits->pluck('gname')->toArray();
            $trait_googlenames = array_unique($trait_googlenames);      //Unique: Some traits (for thermostats) are specified multiple times
            //Necessary workaround: array_unique keeps indexes. ['test', 'test', 'test', 'test2'] becomes [0 => 'test', 3 => 'test2']
            $trait_googlenames = array_values($trait_googlenames);

            $deviceBuilder = [
                'id' => $device->device_id,
                'type' => $device->deviceType->gname,
                'traits' => $trait_googlenames,
                'name' => [
                    'defaultNames' => ['Pecwill Virtual Device'],
                    'name' => $device->name,
                ],
                //when hosted by us we have to implement report state.
                //I do not recommend that in a self-hosted environment since it is just useless effort for this application
                'willReportState' => true,
                'deviceInfo' => [
                    'manufacturer' => 'Pecwill Technologies',
                ],
                'attributes' => [],
            ];

            /** CUSTOM MODIFICATIONS FOR TRAIT: "Scene" */
            if ($device->traits->where('shortname', 'Scene')->count()) {
                $deviceBuilder['willReportState'] = false;  //Scene will never report state, since they are write-only devs
                $deviceBuilder['attributes']['sceneReversible'] = false;
            }
            /** END CUSTOM MODIFICATIONS */

            /** CUSTOM MODIFICATIONS FOR TRAIT: "Thermostat" */
            if ($device->traits->where('shortname', 'TempSet.Mode')->count()) {
                $modes = $device->traits->where('shortname', 'TempSet.Mode');
                $modes = Collection::make(json_decode($modes->first()->pivot->config, true))->get('modesSupported');
                if (empty($modes)) {
                    $modes = ['on', 'off'];
                }

                $deviceBuilder['attributes']['thermostatTemperatureUnit'] = 'C';    //only degrees C are supported as unit
                $deviceBuilder['attributes']['availableThermostatModes'] = implode(',', $modes);
            }
            /** END CUSTOM MODIFICATIONS */

            /** CUSTOM MODIFICATIONS FOR TRAIT "FanSpeed" */

            /**
             * Representation:
             * {"availableFanSpeeds":{"S1":{"names":["Geschwindigkeit1","Langsam"]},"S2":{"names":["Geschwindigkeit2","Mittel"]},"S3":{"names":["Geschwindigkeit3","Schnell"]}}}
             */
            if ($device->traits->where('shortname', 'FanSpeed')->count()) {
                $traitconf = Collection::make(json_decode($device->traits->where('shortname', 'FanSpeed')->first()->pivot->config, true))->get('availableFanSpeeds');
                $availableFanSpeeds = [];
                if ($traitconf) {
                    foreach ($traitconf as $speedName => $speedConf) {
                        $currentSpeedData = [
                            'speed_name' => $speedName,
                            'speed_values' => [],
                        ];

                        $speedNames = [];
                        if (isset($speedConf['names'])) {
                            $speedNames = array_values($speedConf['names']);
                        } else {
                            $speedNames = ['Speed '.$speedName];
                        }

                        foreach (['da', 'nl', 'en', 'fr', 'de', 'hi', 'it', 'ja', 'ko', 'no', 'es', 'sv'] as $lang) {
                            //Names are currently valid for all languages. User probably just uses one
                            $currentSpeedData['speed_values'][] = [
                                'lang' => $lang,
                                'speed_synonym' => $speedNames,
                            ];
                        }

                        $availableFanSpeeds[] = $currentSpeedData;
                    }
                }

                $deviceBuilder['attributes']['reversible'] = false; //Reversing is not yet supported
                $deviceBuilder['attributes']['availableFanSpeeds'] = [
                    'ordered' => true,
                    'speeds' => $availableFanSpeeds,
                ];
            }
            /** END CUSTOM MODIFICATIONS */

            /** CUSTOM MODIFICATIONS FOR TRAIT: "StartStop" */
            if ($device->traits->where('shortname', 'StartStop')->count()) {
                $deviceBuilder['attributes']['pausable'] = false; //Pausing is not yet support, as well as zones
            }
            /** END CUSTOM MODIFICATIONS */

            /** CUSTOM MODIFICATIONS FOR TRAIT: "CameraStream" */
            if ($device->traits->where('shortname', 'CameraStream')->count()) {
                $trait = $device->traits->where('shortname', 'CameraStream')->first();
                $deviceBuilder['willReportState'] = false;
                $deviceBuilder['attributes']['cameraStreamSupportedProtocols'] = [$trait->getCameraStreamConfig()['cameraStreamFormat']];
                $deviceBuilder['attributes']['cameraStreamNeedAuthToken'] = false;
                $deviceBuilder['attributes']['cameraStreamNeedDrmEncryption'] = false;
            }
            /** END CUSTOM MODIFICATIONS */

            /** CUSTOM MODIFICATIONS FOR TRAIT: "ColorSettingRGB"/ "ColorSettingJSON"/ "ColorSettingTemp" */
            if ($device->traits->where('shortname', 'ColorSettingRGB')->count() || $device->traits->where('shortname', 'ColorSettingJSON')->count()) {
                $deviceBuilder['attributes']['colorModel'] = 'rgb';
                $deviceBuilder['attributes']['commandOnlyColorSetting'] = false;
            }
            if ($device->traits->where('shortname', 'ColorSettingTemp')->count()) {
                $deviceBuilder['attributes']['colorModel'] = 'rgb';
                $deviceBuilder['attributes']['commandOnlyColorSetting'] = false;
                $deviceBuilder['attributes']['colorTemperatureRange'] = [
                    'temperatureMinK' => 1000,
                    'temperatureMaxK' => 12000,
                ];
            }
            /** END CUSTOM MODIFICATIONS */

            //Remove attributes key if no attributes are defined
            if (empty($deviceBuilder['attributes'])) {
                unset($deviceBuilder['attributes']);
            }

            $response['payload']['devices'][] = $deviceBuilder;
        }
        //error_log(json_encode($response));
        return response()->json($response);
    }

    /**
     * Handle the Query-Intent
     *
     * @param user The user object
     * @param requestid The request id
     * @param input the data that shall be handled
     */
    public function handleQuery($user, $requestid, $input)
    {
        $response = [
            'requestId' => $requestid,
            'payload' => [
                'devices' => [],
            ],
        ];

        $userid = $user->user_id;

        if (! isset($input['payload']['devices'])) {
            return $this->errorResponse($requestid, ErrorCode::protocolError);
        }

        foreach ($input['payload']['devices'] as $device) {
            $deviceId = $device['id'];
            $device = Device::where('device_id', $deviceId)->get();
            $traits = [];
            if (count($device) > 0) {
                $traits = $device[0]->traits;
            }

            $response['payload']['devices'][$deviceId] = [];
            if (count($traits) > 0) {
                $response['payload']['devices'][$deviceId]['online'] = true;
            } else {
                $response['payload']['devices'][$deviceId]['online'] = false;
            }

            $powerstate = Redis::hget("gbridge:u$userid:d$deviceId", 'power');
            if (! is_null($powerstate)) {
                if ($powerstate == '0') {
                    $response['payload']['devices'][$deviceId]['online'] = false;
                }
            }

            foreach ($traits as $trait) {
                $traitname = strtolower($trait->shortname);

                if (($traitname === 'colorsettingrgb') || ($traitname === 'colorsettingjson') || ($traitname === 'colorsettingtemp')) {
                    $traitname = 'colorsetting';
                }

                $value = Redis::hget("gbridge:u$userid:d$deviceId", $traitname);

                //Special handling/ conversion for certain traits.
                //Setting default values if not set by user before
                if ($traitname == 'onoff') {
                    if (is_null($value)) {
                        $value = false;
                    } else {
                        $value = $value ? true : false;
                    }
                    $traitname = 'on';

                    $response['payload']['devices'][$deviceId][$traitname] = $value;
                } elseif ($traitname == 'brightness') {
                    if (is_null($value)) {
                        $value = 0;
                    } else {
                        $value = intval($value);
                    }

                    $response['payload']['devices'][$deviceId][$traitname] = $value;
                } elseif ($traitname == 'tempset.mode') {
                    if (is_null($value)) {
                        $value = 'off';
                    }

                    $response['payload']['devices'][$deviceId]['thermostatMode'] = $value;
                } elseif ($traitname == 'tempset.setpoint') {
                    if (is_null($value)) {
                        $value = 0.0;
                    } else {
                        $value = floatval($value);
                    }

                    $response['payload']['devices'][$deviceId]['thermostatTemperatureSetpoint'] = $value;
                } elseif ($traitname == 'tempset.ambient') {
                    if (is_null($value)) {
                        $value = 0.0;
                    } else {
                        $value = floatval($value);
                    }

                    $response['payload']['devices'][$deviceId]['thermostatTemperatureAmbient'] = $value;
                } elseif ($traitname == 'tempset.humidity') {
                    if (is_null($value)) {
                        $value = 20.1;
                    } else {
                        $value = floatval($value);
                    }

                    $traitconf = Collection::make(json_decode($trait->pivot->config, true));

                    if ($traitconf->get('humiditySupported')) {
                        $response['payload']['devices'][$deviceId]['thermostatHumidityAmbient'] = $value;
                    }
                } elseif ($traitname == 'fanspeed') {
                    if (is_null($value)) {
                        $availableFanSpeeds = Collection::make(json_decode($trait->pivot->config, true))->get('availableFanSpeeds');

                        if ($availableFanSpeeds && count($availableFanSpeeds)) {
                            $value = array_keys($availableFanSpeeds)[0];
                        } else {
                            $value = 'S1';
                        }
                    }

                    $response['payload']['devices'][$deviceId]['currentFanSpeedSetting'] = $value;
                } elseif ($traitname == 'startstop') {
                    if (is_null($value)) {
                        $value = false;
                    }
                    if ($value) {
                        $value = true;
                    } else {
                        $value = false;
                    }

                    $response['payload']['devices'][$deviceId]['isRunning'] = $value;
                    $response['payload']['devices'][$deviceId]['isPaused'] = false;         //Pausing not yet supported
                } elseif ($traitname == 'openclose') {
                    if (is_null($value)) {
                        $value = 0;
                    } else {
                        $value = intval($value);
                    }

                    $response['payload']['devices'][$deviceId]['openPercent'] = $value;
                } elseif ($traitname == 'colorsetting') {
                    if (is_null($value)) {
                        $value = 'rgb:0';
                    }

                    [$colorType, $colorValue] = explode(':', $value);
                    $colorValue = intval($colorValue);

                    $response['payload']['devices'][$deviceId]['color'] = [];

                    if ($colorType === 'rgb') {
                        $response['payload']['devices'][$deviceId]['color']['spectrumRgb'] = $colorValue;
                    } elseif ($colorType === 'temp') {
                        $response['payload']['devices'][$deviceId]['color']['temperatureK'] = $colorValue;
                    }
                } elseif ($traitname == 'colorsettingtemp') {
                    $value = intval($value);

                    if (! array_key_exists('color', $response['payload']['devices'][$deviceId])) {
                        $response['payload']['devices'][$deviceId]['color'] = [];
                    }

                    $response['payload']['devices'][$deviceId]['color']['spectrumRgb'] = $value;
                } elseif ($traitname == 'scene') {
                    //no value is required for scene, only "online" information
                } else {
                    error_log("Unknown trait:\"$traitname\" for user $userid in query");
                    $response['payload']['devices'][$deviceId]['online'] = false;
                }
            }
        }
        //error_log(json_encode($response));
        return response()->json($response);
    }

    /**
     * Handle the Execute-Intent
     *
     * @param user The user object
     * @param requestid The request id
     * @param input the data that shall be handled
     */
    public function handleExecute($user, $requestid, $input)
    {
        if (! isset($input['payload']['commands'])) {
            return $this->errorResponse($requestid, ErrorCode::protocolError);
        }

        $handledDeviceIds = [];         //array of all device ids that are handled
        $successfulDeviceIds = [];      //array of all device ids that are handled successfully (e.g. are not offline and everything went well)
        $offlineDeviceIds = [];         //array of all device ids that are offline
        $twofaPinDeviceIds = [];        //all devices that require two fa pin code
        $twofaWrongPinDeviceIds = [];   //all devices that require two fa pin code, but wrong pin was given
        $twofaAckDeviceIds = [];        //all devices that require two fa confirmation messages
        $cameraStreamDeviceIds = [];    //all ids of devices that have the camera stream trait

        foreach ($input['payload']['commands'] as $command) {
            $deviceIds = array_map(function ($device) {
                return $device['id'];
            }, $command['devices']);
            $handledDeviceIds = array_merge($handledDeviceIds, $deviceIds);
            foreach ($command['execution'] as $exec) {

                //This code is executed for each device block

                //trait that is requested
                //value that this trait gets

                if ($exec['command'] === 'action.devices.commands.OnOff') {
                    $trait = 'onoff';
                    $value = $exec['params']['on'] ? '1' : '0';
                } elseif ($exec['command'] === 'action.devices.commands.BrightnessAbsolute') {
                    $trait = 'brightness';
                    $value = $exec['params']['brightness'];
                } elseif ($exec['command'] === 'action.devices.commands.ActivateScene') {
                    $trait = 'scene';
                    $value = 1;
                } elseif ($exec['command'] === 'action.devices.commands.ThermostatTemperatureSetpoint') {
                    $trait = 'tempset.setpoint';
                    $value = $exec['params']['thermostatTemperatureSetpoint'];
                } elseif ($exec['command'] === 'action.devices.commands.ThermostatSetMode') {
                    $trait = 'tempset.mode';
                    $value = $exec['params']['thermostatMode'];
                } elseif ($exec['command'] === 'action.devices.commands.SetFanSpeed') {
                    $trait = 'fanspeed';
                    $value = $exec['params']['fanSpeed'];
                } elseif ($exec['command'] === 'action.devices.commands.StartStop') {
                    $trait = 'startstop';
                    $value = $exec['params']['start'] ? 'start' : 'stop';
                } elseif ($exec['command'] === 'action.devices.commands.OpenClose') {
                    $trait = 'openclose';
                    $value = $exec['params']['openPercent'];
                } elseif ($exec['command'] === 'action.devices.commands.GetCameraStream') {
                    $trait = 'camerastream';
                    $value = ($exec['params']['StreamToChromecast'] == true) ? 'chromecast' : 'generic';
                } elseif ($exec['command'] === 'action.devices.commands.ColorAbsolute') {
                    $trait = 'colorsetting';

                    $buildData = ['', '', ''];

                    if (array_key_exists('spectrumRGB', $exec['params']['color'])) {
                        $buildData[0] = 'rgb';
                        $buildData[1] = strval($exec['params']['color']['spectrumRGB']);
                    } elseif (array_key_exists('temperature', $exec['params']['color'])) {
                        $buildData[0] = 'temp';
                        $buildData[1] = strval($exec['params']['color']['temperature']);
                    }

                    if (array_key_exists('name', $exec['params']['color'])) {
                        $buildData[2] = strval($exec['params']['color']['name']);
                    }

                    $value = implode(':', $buildData);
                } else {
                    //unknown execute-command
                    Log::error('Unknown Google execute-command: '.$exec['command']);
                    continue;
                }

                foreach ($deviceIds as $deviceid) {
                    $device = Device::find($deviceid);
                    if (! $device) {
                        Log::error('Google exec for unknown deviceid: '.$deviceid);
                        continue;
                    }

                    if ($device->twofa_type) {
                        //Two factor confirmation is necessary for that device
                        if ($device->twofa_type == 'ack') {
                            //User just needs to confirm that he wants to do that action
                            if (! (isset($exec['challenge']) && isset($exec['challenge']['ack']) && $exec['challenge']['ack'])) {
                                //Ack was not yet given
                                $twofaAckDeviceIds[] = $deviceid;
                                continue;
                            }
                        }
                        if ($device->twofa_type == 'pin') {
                            //User needs to give a pin code
                            if (! (isset($exec['challenge']) && isset($exec['challenge']['pin']) && $exec['challenge']['pin'])) {
                                //Ack was not yet given
                                $twofaPinDeviceIds[] = $deviceid;
                                continue;
                            }

                            if ($exec['challenge']['pin'] != $device->twofa_pin) {
                                $twofaWrongPinDeviceIds[] = $deviceid;
                                continue;
                            }
                        }
                    }

                    //publish the new state to Redis
                    Redis::publish("gbridge:u$user->user_id:d$deviceid:$trait", $value);

                    //Camera streaming ignores power state here
                    if ($trait == 'camerastream') {
                        $cameraStreamDeviceIds[] = $deviceid;
                        continue;
                    }

                    //do not add to successfull devices if marked offline
                    $powerstate = Redis::hget("gbridge:u$user->user_id:d$deviceid", 'power');
                    if (is_null($powerstate) || ($powerstate != '0')) {
                        $successfulDeviceIds[] = $deviceid;
                    } else {
                        $offlineDeviceIds[] = $deviceid;
                    }
                }
            }
        }

        $handledDeviceIds = array_unique($handledDeviceIds);
        $successfulDeviceIds = array_unique($successfulDeviceIds);
        $offlineDeviceIds = array_unique($offlineDeviceIds);

        $response = [
            'requestId' => $requestid,
            'payload' => [
                'commands' => [],
            ],
        ];

        if (count($successfulDeviceIds) > 0) {
            $response['payload']['commands'][] = [
                'ids' => array_values($successfulDeviceIds),
                'status' => 'SUCCESS',
            ];
        }
        if (count($offlineDeviceIds) > 0) {
            $response['payload']['commands'][] = [
                'ids' => array_values($offlineDeviceIds),
                'status' => 'OFFLINE',
            ];
        }
        if (count($twofaAckDeviceIds) > 0) {
            $response['payload']['commands'][] = [
                'ids' => array_values($twofaAckDeviceIds),
                'status' => 'ERROR',
                'errorCode' => 'challengeNeeded',
                'challengeNeeded' => [
                    'type' => 'ackNeeded',
                ],
            ];
        }
        if (count($twofaPinDeviceIds) > 0) {
            $response['payload']['commands'][] = [
                'ids' => array_values($twofaPinDeviceIds),
                'status' => 'ERROR',
                'errorCode' => 'challengeNeeded',
                'challengeNeeded' => [
                    'type' => 'pinNeeded',
                ],
            ];
        }
        if (count($twofaWrongPinDeviceIds) > 0) {
            $response['payload']['commands'][] = [
                'ids' => array_values($twofaWrongPinDeviceIds),
                'status' => 'ERROR',
                'errorCode' => 'challengeNeeded',
                'challengeNeeded' => [
                    'type' => 'challengeFailedPinNeeded',
                ],
            ];
        }

        foreach ($cameraStreamDeviceIds as $cameraStreamDeviceId) {
            $device = Device::find($cameraStreamDeviceId);
            if (! $device) {
                continue;
            }
            $url = Redis::hget("gbridge:u$user->user_id:d$device->device_id", 'camerastream');
            if (is_null($url)) {
                //Not set by user via MQTT (-> not in cache), check for defaults
                $trait = $device->traits->where('shortname', 'CameraStream')->first();
                if (is_null($trait)) {
                    //Somehow can't find a CameraStream trait for this device
                    $url = '';
                } else {
                    $url = $trait->getCameraStreamConfig()['cameraStreamDefaultUrl'];
                    if (is_null($url)) {
                        //Also no default is set.
                        $url = '';
                    }
                }
            }
            $response['payload']['commands'][] = [
                'ids' => [$cameraStreamDeviceId],
                'status' => 'SUCCESS',
                'states' => [
                    'cameraStreamAccessUrl' => $url,
                ],
            ];
        }

        //error_log(json_encode($response));
        return response()->json($response);
    }

    /**
     * Send an error message back
     */
    private function errorResponse($requestid, $errorcode)
    {
        $error = [
            'requestId' => $requestid,
            'payload' => [
                'errorCode' => $errorcode,
            ],
        ];

        return response()->json($error);
    }
}

//error codes that can be returned
abstract class ErrorCode
{
    const authExpired = 'authExpired';

    const authFailure = 'authFailure';

    const deviceOffline = 'deviceOffline';

    const timeout = 'timeout';

    const deviceTurnedOff = 'deviceTurnedOff';

    const deviceNotFound = 'deviceNotFound';

    const valueOutOfRange = 'valueOutOfRange';

    const notSupported = 'notSupported';

    const protocolError = 'protocolError';

    const unknownError = 'unknownError';
}
