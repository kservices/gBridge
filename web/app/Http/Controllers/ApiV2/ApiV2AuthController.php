<?php

namespace App\Http\Controllers\ApiV2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use App\ApiKey;

class ApiV2AuthController extends Controller
{
    /**
     * Create a new ApiV2AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:apiv2', ['except' => ['token']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function token()
    {
        //Authentication with login_username and password -> only for accounts created by resellers
        if(request('login_username') && request('password')){
            $credentials = request(['login_username', 'password']);

            if (! $token = auth('apiv2')->claims(['privilege_user' => true])->attempt(["login_username" => request("login_username"), "password" => request("password")])) {
                return response()->json(['error_code' => 'unauthorized'], 401);
            }
            return $this->respondWithToken($token);
        }

        //authentication using an api key
        $apikeyString = request('apikey');
        $apikey = ApiKey::where('key', $apikeyString)->get()->first();
        if(!$apikey){
            return response()->json(['error_code' => 'unauthorized'], 401);
        }

        $claims = [];
        if($apikey->privilege_user){
            $claims['privilege_user'] = true;
        }

        if (!$token = auth('apiv2')->claims($claims)->login($apikey->user)) {
            return response()->json(['error_code' => 'unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('apiv2')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('apiv2')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('apiv2')->factory()->getTTL() * 60,
            'privilege' => 'standard' . (auth('apiv2')->payload()->get('privilege_user') ? ',user':'')
        ]);
    }
}
