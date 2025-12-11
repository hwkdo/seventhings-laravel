<?php
namespace Hwkdo\SeventhingsLaravel\Models;

use \Carbon\Carbon;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravelFacade as SeventhingsLaravel;


class Filiale extends Eloquent
{
    private $id;
    private $datev_id;
    
    

    public function __construct($row)
    {
        $this->row = $row;
        $this->id = $row->id;
        $this->datev_id = $row->name;        
        $this->filialnamen = [
            '5' => 'BZA Haus A',
            '6' => 'BZA Haus 1',
            '7' => 'BZA Haus 2',
            '8' => 'Reinoldistr',
            '9' => 'BZ Körne',
            '10' => 'BZ Ruhr',
            '11' => 'BZ Soest',
            '12' => 'BZ Hansemann',
            '1' => 'Hansemann Internat',
            '14' => 'Keine Ahnung',
        ];
    }    

    public function getRaeumeAttribute()
    {
        return SeventhingsLaravel::getFilialRaeume($this->id);
    }

    public function getRawData($column = null)
    {
        if($column) {
            return $this->row->$column;
        }
        return $this->row;
    }

    public function getIdAttribute()
    {
        return $this->id;
    }

    public function getDatevIdAttribute()
    {
        return (int)$this->datev_id;
    }

    public function getNameAttribute()
    {
        if (array_key_exists($this->id,$this->filialnamen))
        {
            return $this->filialnamen[$this->id];
        }
        else {
            return 'Unbekannt';
        }
    }
    
}