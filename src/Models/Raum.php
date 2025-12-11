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
        $this->id = $row->id;
        $this->nummer = $row->number ?? null;
        $this->etage = $row->flur_97bcff6f ?? null;
        $this->name = $row->name ?? null;            
        $this->mitarbeiter = $row->nutzer_c9dfc7fd ?? null;//$row->s_11;
        $this->gebaeude = $row->standort_filiale_id_d5500dbf ?? null;
        $this->kostenstelle = $row->kostenstelle_cc7633d2 ?? null; //$row->s_14;
    }    

    public function getRawData($column = null)
    {
        if($column) {
            return $this->row->$column;
        }
        return $this->row;
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