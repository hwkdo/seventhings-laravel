<?php
namespace Hwkdo\SeventhingsLaravel\Models;

use \Carbon\Carbon;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravelFacade as SeventhingsLaravel;

class Raum extends Eloquent
{
    protected $appends = [
        'id',
        'nummer',
        'etage',
        'name',
        'mitarbeiter',
        'gebaeude',
        'kostenstelle',
        'label'
    ];

    private $id;
    private $nummer;
    private $etage;
    private $name;
    private $mitarbeiter;
    private $gebaeude;
    private $kostenstelle;
    

    public function __construct($row)
    {
        $this->row = $row;
        $this->id = $this->readRowProperty($row, 'id');
        $this->nummer = $this->readRowProperty($row, 'number');
        $this->etage = $this->readRowProperty($row, 'flur_97bcff6f');
        $this->name = $this->readRowProperty($row, 'name');
        $this->mitarbeiter = $this->readRowProperty($row, 'nutzer_c9dfc7fd');
        $this->gebaeude = $this->readRowProperty($row, 'standort_filiale_id_d5500dbf');
        $this->kostenstelle = $this->readRowProperty($row, 'kostenstelle_cc7633d2');
    }

    public function getRawData($column = null)
    {
        if ($column) {
            return $this->readRowProperty($this->row, $column);
        }

        return $this->row;
    }

    /**
     * Laravel wandelt Undefined-Property-Warnings in ErrorExceptions um —
     * daher nie Rohzugriff ohne property_exists.
     */
    private function readRowProperty(mixed $row, string $property): mixed
    {
        if (! is_object($row) || ! property_exists($row, $property)) {
            return null;
        }

        return $row->$property;
    }

    public function getLabelAttribute()
    {
        $s = $this->nummer;
        if($this->name || $this->mitarbeiter) $s .= '(';
        if ($this->name && !$this->mitarbeiter) $s.= $this->name;
        if ($this->name && $this->mitarbeiter) $s.= $this->name.'/'.$this->mitarbeiter;
        if($this->name || $this->mitarbeiter) $s .= ')';
        return $s;
    }

    public function getFilialeAttribute()
    {
        return SeventhingsLaravel::findFilialeById($this->getRawData('building_id'));
    }

    public function getAssetsAttribute()
    {
        return SeventhingsLaravel::getAssetsInRaum($this->id);
    }

    public function getIdAttribute()
    {
        return $this->id;
    }

    public function getNummerAttribute()
    {
        return $this->nummer;
    }

    public function getEtageAttribute()
    {
        return $this->etage;
    }

    public function getNameAttribute()
    {
        return $this->name;
    }

    public function getMitarbeiterAttribute()
    {
        return $this->mitarbeiter;
    }

    public function getGebaeudeAttribute()
    {
        return $this->gebaeude;
    }

    public function getKostenstelleAttribute()
    {
        return $this->kostenstelle;
    }

}