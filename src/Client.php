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
        if ($result->getStatusCode() == 200) return json_decode($result->getBody()->getContents());
        else return  $result->getReasonPhrase();
    }

    public function updateAsset($itexia_id,$payload)
    {
        $payload = json_encode($payload);
        $result = $this->sendRequest('PUT','objects/update?id='.$itexia_id,$payload);
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