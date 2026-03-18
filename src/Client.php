<?php

namespace Hwkdo\SeventhingsLaravel;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;
use Hwkdo\SeventhingsLaravel\Models\Raum as ItexiaRaum;
use Hwkdo\SeventhingsLaravel\Models\Filiale as ItexiaFiliale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Client extends Eloquent
{

    /*
    **  https://api.seventhings.com/customer-api/v1/
    */

    public static function baseUrl()
    {
        return config('seventhings-laravel.url').config('seventhings-laravel.version').'/';
    }

    public function sendHttpRequest($verb,$endpoint,$payload)
    {
        $response = Http::withHeaders([            
                 'Accept' => 'application/json',
                 'Content-Type'  => 'application/json',
        ])->withToken(Token::getToken())
        ->$verb(self::baseUrl().$endpoint,$payload);

        return $response->ok() ? json_decode($response->getBody()->getContents()) : $response->getReasonPhrase();
    }

    public function sendRequest($verb,$endpoint,$payload = null)
    {
        $guzzle = new \GuzzleHttp\Client(
            ['base_uri' => self::baseUrl()]
        );
        $request = new \GuzzleHttp\Psr7\Request($verb,
            $endpoint,
            [
                 'Authorization' => 'Bearer '.Token::getToken(),
                 'Accept' => 'application/json',
                 'Content-Type'  => 'application/json',
            ],
            $payload
        );
        $result = $guzzle->send($request);
        $status = $result->getStatusCode();
        if ($status === 200) {
            return json_decode($result->getBody()->getContents());
        }
        if ($status === 204) {
            return (object) ['_status' => 204];
        }

        return $result->getReasonPhrase();
    }

    /**
     * Update eines Objekts per PATCH object/{objectUuid} (Seventhings Customer API).
     * Die API antwortet bei Erfolg mit 204 No Content (kein Body).
     *
     * @param  string  $objectUuid  UUID des Objekts (asset_uuid aus API-Response)
     * @param  array<string, mixed>  $payload  Zu aktualisierende Felder (field_key => value)
     * @return ItexiaAsset|null  Bei 204 wird null zurückgegeben (Erfolg, kein Body)
     *
     * @throws \RuntimeException Bei API-Fehler (z. B. 404)
     */
    public function updateAsset(string $objectUuid, array $payload)
    {
        $body = json_encode($payload);
        $result = $this->sendRequest('PATCH', 'object/'.$objectUuid, $body);

        if (! is_object($result)) {
            throw new \RuntimeException(
                is_string($result) ? $result : 'Unbekannter API-Fehler beim Objekt-Update.'
            );
        }
        if (isset($result->_status) && $result->_status === 204) {
            return null;
        }

        return new ItexiaAsset($result);
    }

    public function findAsset($barcode)
    {
        $result = $this->sendRequest('GET','objects?filter[barcode][eq]='.$barcode);
        if(count($result->items) == 1) {
            return new ItexiaAsset($result->items[0]);
        } 
        else return null;
    }

    public function findAssetBySn($sn)
    {

        $result = $this->sendRequest('GET','objects?filter[custom_4][eq]='.$sn);
//        $result = \DB::connection('itexia')->table('itexia.Asset')->where('barcode',$barcode)->get();
        if(count($result->items) == 1) {
            return new ItexiaAsset($result->items[0]);
        }
        else return null;
    }

    /**
     * Findet ein Asset anhand der Rechnungsnummer (API-Feld rechnungsnummer_b0eb3192).
     *
     * @param  string  $rechnungsnummer
     * @return ItexiaAsset|null
     */
    public function findAssetByRechnungsnummer($rechnungsnummer)
    {
        $value = trim((string) $rechnungsnummer);
        if ($value === '') {
            return null;
        }
        $filter = 'objects?filter[rechnungsnummer_b0eb3192][eq]='.rawurlencode($value);
        $result = $this->sendRequest('GET', $filter);
        if (is_object($result) && isset($result->items) && count($result->items) === 1) {
            return new ItexiaAsset($result->items[0]);
        }

        return null;
    }

    public function findFilialeById($id)
    {
        $result = $this->sendRequest('GET','locations?filter[id][eq]='.$id);
        if(count($result->items) == 1) {
            return new ItexiaFiliale($result->items[0]);
        }
        else return null;

//        $result = \DB::connection('itexia')->table('itexia.Building')->where('id',$id)->get();
//        if($result->count() == 1) {
//            return new ItexiaFiliale($result->first());
//        }
//        else return null;
    }


    public function findRaumById($id)
    {
        $result = $this->sendRequest('GET','rooms?filter[id][eq]='.$id);
        if(count($result->items) == 1) {
            return new ItexiaRaum($result->items[0]);
        }
        else return null;

//        $result = \DB::connection('itexia')->table('itexia.Room')->where('id',$id)->get();
//        if($result->count() == 1) {
//            return new ItexiaRaum($result->first());
//        }
//        else return null;
    }
    
    
   
    public function getFilialRaeume($id)
    {
        $result = $this->sendRequest('GET','rooms?filter[building_id][eq]='.$id);
        $col = collect();
        foreach($result->items as $row)
        {
            $col->push(new ItexiaRaum($row));
        }
        return $col;
        // $result = \DB::connection('itexia')->table('itexia.Room')->where('building_id',$id)->get();
        // if($result->count() == 1) {
        //     return new ItexiaRaum($result->first());
        // } 
        // elseif($result->count() > 1) 
        // {
        //     $col = collect();
        //     foreach($result as $row)
        //     {
        //         $col->push(new ItexiaRaum($row));
        //     }
        //     return $col;
        // }
        // else return null;
    }

    public function getRaeume()
    {
        $page = 1;
        $result = $this->sendRequest('GET','rooms?per_page=1000&page='.$page);
        $items = $result->items;
        if($result->total > ($result->per_page * $result->page)) {
            $page++;
            $result = $this->sendRequest('GET','rooms?per_page=1000&page='.$page);            
            $items = array_merge($items, $result->items);
        }
        $col = collect();
        foreach($items as $row)
        {
            $col->push(new ItexiaRaum($row));
        }
        return $col;
    }

    public function getGebaeude()
    {
        $result = $this->sendRequest('GET','locations');
        return $result->items;
        $col = collect();
        foreach($result->items as $row)
        {
            $col->push(new ItexiaFiliale($row));
        }
        return $col;
    }

    public function getAssetsInRaum($raum)
    {
        $result = $this->sendRequest('GET','objects?filter[actual_room][eq]='.$raum);
        $col = collect();
        foreach($result->items as $row)
        {
            $col->push(new ItexiaAsset($row));
        }
        return $col;
    }

    /**
     * Legt ein neues Objekt in Itexia/Seventhings an (POST object, Customer API).
     * Response 201 mit Location-Header; die Objekt-UUID wird aus dem Location-Pfad extrahiert.
     *
     * @param  array<string, mixed>  $payload  Feld-Schlüssel => Wert (z. B. barcode, custom_78, inventory_name, custom_4, actual_room, rechnungsnummer_b0eb3192)
     * @return string  UUID des neu erstellten Objekts (aus Location-Header)
     *
     * @throws \RuntimeException Bei API-Fehler oder fehlendem Location-Header
     */
    public function createAsset(array $payload): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withToken(Token::getToken())
            ->post(self::baseUrl().'object', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                $response->reason().($response->body() ? ': '.$response->body() : '')
            );
        }

        $location = $response->header('Location');
        if ($location === null || $location === '') {
            throw new \RuntimeException('Create-Objekt: Location-Header fehlt in der API-Response.');
        }

        $path = parse_url($location, PHP_URL_PATH);
        $uuid = $path !== null && $path !== '' ? trim((string) basename($path), '/') : '';

        if ($uuid === '') {
            throw new \RuntimeException('Create-Objekt: UUID konnte aus Location nicht ermittelt werden.');
        }

        return $uuid;
    }

    public function createRoom($data)
    {
        $result = $this->sendHttpRequest('POST','rooms/create',$data);
        return $result;        
    }

    public function updateRoom($data,$id)
    {
        $result = $this->sendHttpRequest('PUT','rooms/update?id='.$id,$data);
        return $result;        
    }

    public function createGebaeude($data)
    {
        $result = $this->sendHttpRequest('POST','buildings/create',$data);
        return $result;        
    }

    public function updateGebaeude($data, $id)
    {
        $result = $this->sendHttpRequest('PUT','buildings/update?id='.$id,$data);
        return $result;        
    }

    public function getRaumFelddefintionen($onlyRequired = false)
    {
        $endpoint = $onlyRequired ? 'room-field-definition?filter[mandatory][eq]=1' : 'room-field-definition';
        return $this->sendHttpRequest('GET',$endpoint,null);
    }

}