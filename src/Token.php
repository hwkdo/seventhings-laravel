<?php

namespace Hwkdo\SeventhingsLaravel;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $guarded = [];
    protected $table = 'seventhings_laravel_tokens';
    protected $casts = ['expiration' => 'datetime'];

    public static function getToken($model = false)
    {
        $newest = self::orderBy('expiration')->first();
        if($newest && $newest->expiration > \Carbon\Carbon::now())
        {
            return $model ? $newest : $newest->token;
        }
        elseif(!$newest) {
            return self::newToken($model);
        }
        $newest->delete();
        return self::newToken($model);
    }

    private static function newToken($model = false) : String
    {
        $guzzle = new \GuzzleHttp\Client();
        $url = Client::baseUrl().'auth_token';
        $accessToken = json_decode($guzzle->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'client_id' => config('seventhings-laravel.client_id'),
                'username' => config('seventhings-laravel.username'),
                'password' => config('seventhings-laravel.password'),
                'grant_type' => config('seventhings-laravel.grant_type'),
            ],
        ])->getBody()->getContents());
        $token = self::create([
            'token' => $accessToken->access_token,
            'refresh_token' => $accessToken->refresh_token,
            'expiration' => \Carbon\Carbon::now()->addSeconds($accessToken->expires_in)
        ]);

        return $model ? $token : $token->token;
    }

    public static function refreshToken() : String
    {
        $guzzle = new \GuzzleHttp\Client();
        $url = Client::baseUrl().'auth_token';
        $accessToken = json_decode($guzzle->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [       
                'client_id' => config('seventhings-laravel.client_id'),         
                'refresh_token' => self::getToken(true)->refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ])->getBody()->getContents());
        $token = self::create([
            'token' => $accessToken->access_token,
            'refresh_token' => $accessToken->refresh_token,
            'expiration' => \Carbon\Carbon::now()->addSeconds($accessToken->expires_in)
        ]);

        return $token->token;
    }
}
