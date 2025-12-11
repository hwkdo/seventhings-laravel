<?php

namespace Hwkdo\SeventhingsLaravel;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\Client;


class SeventhingsLaravel extends Client
{
// Diese Klasse hier ist nur für die Facade. ItexiaLaravel::Methode() ruft die nicht-statische Methode in dieser Klasse hier auf.
// durch das extends Client werden dann die Methoden aus der Client-Klasse aufgerufen.
}