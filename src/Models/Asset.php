<?php
namespace Hwkdo\SeventhingsLaravel\Models;

use \Carbon\Carbon;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\SeventhingsLaravelFacade as SeventhingsLaravel;

class Asset extends Eloquent
{
    protected $appends = [
        'barcode',
        'beschreibung',
        'sn',
        'preis',
        'kostenstelle',
        'datev_nr',
        'rechnungsnummer',
        'einheit',
        'lieferdatum',
        'raum_soll',
        'raum_ist',
        'konto',
        'kontobeschriftung',
        'nutzungsart',
        'versicherungsart',
        'nutzungsdauer',
        'gefoerdert',
        'external_id',
        'halbwertszeit',
        'geraeteart'
    ];
    private $row;
    private $barcode;
    private $beschreibung;
    private $sn;
    private $preis;
    private $kostenstelle;
    private $datev_nr;

    /** Rechnungsnummer aus API-Feld rechnungsnummer_b0eb3192 */
    private $rechnungsnummer;
    private $einheit;
    private $lieferdatum;
    private $raum_soll;
    private $raum_ist;
    private $konto;
    private $kontobeschriftung;
    private $nutzungsart;
    private $versicherungsart;
    private $nutzungsdauer;
    private $external_id;
    private $halbwertszeit;
    private $geraeteart;

    public function __construct($row)
    {
        $this->row = $row;
//        $this->barcode = $row->barcode;      //barcode
//        $this->beschreibung = $row->s_05;    //inventory_name
//        $this->sn = $row->s_16;    //custom_4"
//        $this->preis = $row->i_01;    //preis_hist_anschaffungskosten_eff27c3b
//        $this->kostenstelle = $row->s_00;      //custom_84
//        $this->datev_nr = $row->s_02;    //custom_78
//        $this->einheit = $row->s_19;    //custom_93
//        $this->lieferdatum = $row->i_00;
//        $this->raum_soll = $row->i_04;   //target_room
//        $this->raum_ist = $row->i_05;   //actual_room
//        $this->konto = $row->s_09;  //custom_79
//        $this->kontobeschriftung = $row->s_10; //custom_80
//        $this->nutzungsart = $row->s_41; //nutzungsart_c855189d
//        $this->versicherungsart = $row->s_37; //versicherungsart_alt_f1107381
//                                                //oder versicherungsart_43f20af4
//        $this->nutzungsdauer = $row->s_11; //custom_83
        $this->barcode = $row->barcode;      //barcode
		$this->beschreibung = $row->inventory_name ?? null;    //inventory_name
		$this->sn = $row->custom_4 ?? null;    //custom_4"
		$this->preis = $row->preis_hist_anschaffungskosten_eff27c3b ?? null;    //preis_hist_anschaffungskosten_eff27c3b
		$this->kostenstelle = $row->custom_84 ?? null;      //custom_84
		$this->datev_nr = $row->custom_78 ?? null;    //custom_78
		$this->rechnungsnummer = $row->rechnungsnummer_b0eb3192 ?? null;
		$this->einheit = $row->custom_93 ?? null;    //custom_93
		$this->lieferdatum = $row->purchasing_date ?? null;
		$this->raum_soll = $row->target_room ?? null;   //target_room
		$this->raum_ist = $row->actual_room ?? null;   //actual_room
		$this->konto = $row->custom_79 ?? null;  //custom_79
		$this->kontobeschriftung = $row->custom_80 ?? null; //custom_80
		$this->nutzungsart = $row->nutzungsart_c855189d ?? null; //nutzungsart_c855189d
		$this->versicherungsart = ($row->versicherungsart_43f20af4 ?? ($row->versicherungsart_alt_f1107381 ?? null)); //alternativer Fallback
        //oder versicherungsart_43f20af4
		$this->nutzungsdauer = $row->custom_83 ?? null; //custom_83
		$this->external_id = $row->id ?? null;
		$this->halbwertszeit = $row->technische_halbwertszeit_16703785 ?? null; //halbwertszeit_50999073
		$this->geraeteart = $row->ger_teart_b9efdd60 ?? null; //geraeteart_82322711
    }

    public function getRawData($column = null)
    {
		if ($column) {
			return property_exists($this->row, $column) ? $this->row->$column : null;
		}
		return $this->row;
    }

    public function getBarcodeAttribute()
    {
        return $this->barcode;
    }

    public function getBeschreibungAttribute()
    {
        return $this->beschreibung;
    }

    public function getSnAttribute()
    {
        return $this->sn;
    }

    public function getPreisAttribute()
    {
        /*
        $len = strlen($this->preis);
        $euro = substr($this->preis,0,$len-2);
        $cent = substr($this->preis,$len-2);
        return $euro.','.$cent;
        */
        return $this->preis;

    }

    public function getKostenstelleAttribute()
    {
        return $this->kostenstelle;
    }

    public function getDatevNrAttribute()
    {
        return $this->datev_nr;
    }

    public function getRechnungsnummerAttribute()
    {
        return $this->rechnungsnummer;
    }

    public function getEinheitAttribute()
    {
        return $this->einheit;
    }

    public function getLieferdatumAttribute()
    {
        return $this->lieferdatum ? Carbon::parse($this->lieferdatum)->format('d.m.Y') : null;
    }

    public function getRaumSollAttribute()
    {
        return SeventhingsLaravel::findRaumById($this->raum_soll);
    }

    public function getRaumIstAttribute()
    {
        return SeventhingsLaravel::findRaumById($this->raum_ist);
    }

    public function getKontoAttribute()
    {
        return $this->konto;
    }

    public function getKontobeschriftungAttribute()
    {
        return $this->kontobeschriftung;
    }

    public function getNutzungsartAttribute()
    {
        return $this->nutzungsart;
    }

    public function getVersicherungsartAttribute()
    {
        return $this->versicherungsart;
    }

    public function getNutzungsdauerAttribute()
    {
        return $this->nutzungsdauer;
    }

    public function getGefoerdertAttribute()
    {
		$prefix = substr((string)$this->konto, 0, 3);
		if ($prefix === '672' || $prefix === '625') {
			return true;
		} else {
			return false;
		}
    }

    public function getExternalIdAttribute()
    {
        return $this->external_id;
    }

    public function getHalbwertszeitAttribute()
    {
        return $this->halbwertszeit;
    }

    public function getGeraeteartAttribute()
    {
        return $this->geraeteart;
    }
}