<?php
namespace Hwkdo\SeventhingsLaravel;
use Illuminate\Support\Facades\Facade;

class SeventhingsLaravelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'seventhings-laravel';
    }
}