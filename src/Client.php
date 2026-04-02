<?php

namespace Hwkdo\SeventhingsLaravel;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\SeventhingsLaravel\Models\Asset as ItexiaAsset;
use Hwkdo\SeventhingsLaravel\Models\Filiale as ItexiaFiliale;
use Hwkdo\SeventhingsLaravel\Models\Raum as ItexiaRaum;
use Illuminate\Support\Facades\Http;
use stdClass;

class Client extends Eloquent
{
    /**
     * Memoization für {@see findRaumById()}: gleiche Raum-ID löst nur einen GET rooms aus.
     * Die Facade bindet {@see SeventhingsLaravel} als Singleton → Cache gilt pro Request/Job.
     *
     * @var array<string, ItexiaRaum|null>
     */
    protected array $raumByIdLookupCache = [];

    /*
    **  https://api.seventhings.com/customer-api/v1/
    */

    public static function baseUrl()
    {
        return config('seventhings-laravel.url').config('seventhings-laravel.version').'/';
    }

    /**
     * Bearer-Token für Customer-API-Aufrufe (in Tests überschreibbar).
     */
    protected function bearerToken(): string
    {
        return Token::getToken();
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


    /**
     * Raum per ID (GET rooms). Ergebnisse werden pro Client-Instanz gecacht.
     *
     * @return ItexiaRaum|null
     */
    public function findRaumById($id)
    {
        if ($id === null || $id === '' || $id === false) {
            return null;
        }

        $key = (string) $id;
        if (array_key_exists($key, $this->raumByIdLookupCache)) {
            return $this->raumByIdLookupCache[$key];
        }

        $result = $this->sendRequest('GET', 'rooms?filter[id][eq]='.$id);

        if (! is_object($result) || ! isset($result->items) || ! is_array($result->items)) {
            $this->raumByIdLookupCache[$key] = null;

            return null;
        }

        if (count($result->items) === 1) {
            $room = new ItexiaRaum($result->items[0]);
            $this->raumByIdLookupCache[$key] = $room;

            return $room;
        }

        $this->raumByIdLookupCache[$key] = null;

        return null;
    }

    /**
     * Cache von {@see findRaumById()} leeren (Tests, lange Prozesse mit vielen IDs).
     */
    public function flushRaumLookupCache(): void
    {
        $this->raumByIdLookupCache = [];
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

    /**
     * Metadaten einer Datei (Customer API: GET file/{fileUuid}).
     *
     * @return stdClass{uuid?: string, name?: string, type?: string, size?: int, data_uri?: string, thumbnail_uri?: string, ...}
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function getFileMetadata(string $fileUuid): stdClass
    {
        $response = Http::withToken($this->bearerToken())
            ->acceptJson()
            ->get(self::baseUrl().'file/'.$fileUuid);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Datei-Metadaten konnten nicht geladen werden: '.$response->reason().
                ($response->body() !== '' ? ' — '.$response->body() : '')
            );
        }

        $result = $response->object();
        if (! $result instanceof stdClass) {
            throw new \RuntimeException('Datei-Metadaten: ungültige API-Antwort.');
        }

        return $result;
    }

    /**
     * Binärdaten einer Datei (z. B. Bild) laden (GET file/{fileUuid}/data).
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function downloadFileData(string $fileUuid): string
    {
        $response = Http::withToken($this->bearerToken())
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->get(self::baseUrl().'file/'.$fileUuid.'/data');

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Datei-Download fehlgeschlagen: '.$response->reason().
                ($response->body() !== '' ? ' — '.$response->body() : '')
            );
        }

        return $response->body();
    }

    /**
     * Thumbnail-Binärdaten laden (GET file/{fileUuid}/thumbnail).
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function downloadFileThumbnail(string $fileUuid): string
    {
        $response = Http::withToken($this->bearerToken())
            ->withHeaders(['Accept' => 'application/octet-stream'])
            ->get(self::baseUrl().'file/'.$fileUuid.'/thumbnail');

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Thumbnail-Download fehlgeschlagen: '.$response->reason().
                ($response->body() !== '' ? ' — '.$response->body() : '')
            );
        }

        return $response->body();
    }

    /**
     * Neue Datei-Ressource anlegen (POST file, multipart/form-data, Feld „data“).
     * Die UUID stammt aus dem Location-Header der 201-Response.
     *
     * @throws \RuntimeException Bei API-Fehler oder fehlendem Location-Header
     */
    public function uploadFile(string $contents, ?string $filename = null): string
    {
        $filename = $filename ?? 'upload.bin';

        $response = Http::withToken($this->bearerToken())
            ->acceptJson()
            ->attach('data', $contents, $filename)
            ->post(self::baseUrl().'file');

        if ($response->status() !== 201) {
            throw new \RuntimeException(
                'Datei-Upload fehlgeschlagen: '.$response->reason().
                ($response->body() !== '' ? ': '.$response->body() : '')
            );
        }

        $location = $response->header('Location');
        if ($location === null || $location === '') {
            throw new \RuntimeException('Datei-Upload: Location-Header fehlt in der API-Response.');
        }

        $uuid = $this->extractUuidFromLocation($location);

        if ($uuid === '') {
            throw new \RuntimeException('Datei-Upload: UUID konnte aus Location nicht ermittelt werden.');
        }

        return $uuid;
    }

    /**
     * Hochgeladene Datei an ein Objektfeld hängen (POST object/{objectUuid}/add-file).
     *
     * @param  string  $fieldKey  API-Schlüssel der Felddefinition (z. B. „picture“)
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function addFileToObject(string $objectUuid, string $fieldKey, string $fileUuid): void
    {
        $payloadCandidates = [
            [[
                'field-key' => $fieldKey,
                'file-uuid' => $fileUuid,
            ]],
            [[
                'field_key' => $fieldKey,
                'file_uuid' => $fileUuid,
            ]],
            [[
                'field' => $fieldKey,
                'uuid' => $fileUuid,
            ]],
        ];

        $lastResponse = null;
        foreach ($payloadCandidates as $payload) {
            $response = Http::withToken($this->bearerToken())
                ->asJson()
                ->acceptJson()
                ->post(self::baseUrl().'object/'.$objectUuid.'/add-file', $payload);

            if ($response->status() === 200) {
                return;
            }

            if ($response->status() === 207) {
                throw new \RuntimeException(
                    'Datei konnte dem Objekt nicht zugeordnet werden: '.$response->reason().
                    ($response->body() !== '' ? ': '.$response->body() : '')
                );
            }

            if ($response->status() === 400 && !str_contains($response->body(), 'Body does not match schema')) {
                throw new \RuntimeException(
                    'Datei konnte dem Objekt nicht zugeordnet werden: '.$response->reason().
                    ($response->body() !== '' ? ': '.$response->body() : '')
                );
            }

            $lastResponse = $response;
        }

        if ($lastResponse !== null) {
            throw new \RuntimeException(
                'Datei konnte dem Objekt nicht zugeordnet werden: '.$lastResponse->reason().
                ($lastResponse->body() !== '' ? ': '.$lastResponse->body() : '')
            );
        }
    }

    /**
     * Datei von einem Objektfeld entfernen (POST object/{objectUuid}/remove-file).
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function removeFileFromObject(string $objectUuid, string $fieldKey, string $fileUuid): void
    {
        $this->removeFilesFromObject($objectUuid, $fieldKey, [$fileUuid]);
    }

    /**
     * Mehrere Dateien von einem Objektfeld entfernen (POST object/{objectUuid}/remove-file).
     *
     * @param  array<int, string>  $fileUuids
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function removeFilesFromObject(string $objectUuid, string $fieldKey, array $fileUuids): void
    {
        $entries = [];
        foreach ($fileUuids as $fileUuid) {
            $uuid = trim((string) $fileUuid);
            if ($uuid === '') {
                continue;
            }
            $entries[] = $uuid;
        }

        if ($entries === []) {
            return;
        }

        $payloadCandidates = [
            array_map(
                fn (string $uuid): array => ['field-key' => $fieldKey, 'file-uuid' => $uuid],
                $entries
            ),
            array_map(
                fn (string $uuid): array => ['field_key' => $fieldKey, 'file_uuid' => $uuid],
                $entries
            ),
            array_map(
                fn (string $uuid): array => ['field' => $fieldKey, 'uuid' => $uuid],
                $entries
            ),
        ];

        $lastResponse = null;
        foreach ($payloadCandidates as $payload) {
            $response = Http::withToken($this->bearerToken())
                ->asJson()
                ->acceptJson()
                ->post(self::baseUrl().'object/'.$objectUuid.'/remove-file', $payload);
            if ($response->status() === 200) {
                return;
            }

            if ($response->status() === 207) {
                throw new \RuntimeException(
                    'Dateien konnten vom Objekt nicht entfernt werden: '.$response->reason().
                    ($response->body() !== '' ? ': '.$response->body() : '')
                );
            }

            if ($response->status() === 400 && !str_contains($response->body(), 'Body does not match schema')) {
                throw new \RuntimeException(
                    'Dateien konnten vom Objekt nicht entfernt werden: '.$response->reason().
                    ($response->body() !== '' ? ': '.$response->body() : '')
                );
            }

            $lastResponse = $response;
        }

        if ($lastResponse !== null) {
            throw new \RuntimeException(
                'Dateien konnten vom Objekt nicht entfernt werden: '.$lastResponse->reason().
                ($lastResponse->body() !== '' ? ': '.$lastResponse->body() : '')
            );
        }
    }

    /**
     * Entfernt alle Bilder des Standard-Felds "picture".
     * Primär via remove-file, Fallback via PATCH picture=[].
     *
     * @param  array<int, string>  $pictureUuids
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function removePicturesFromObject(string $objectUuid, array $pictureUuids): void
    {
        try {
            $this->removeFilesFromObject($objectUuid, 'picture', $pictureUuids);
        } catch (\Throwable) {
            // Fallback für Instanzen, deren remove-file/add-file Schema abweicht.
            $this->updateAsset($objectUuid, ['picture' => []]);
        }
    }

    private function extractUuidFromLocation(string $location): string
    {
        $path = (string) (parse_url($location, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return '';
        }

        if (preg_match(
            '/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12})/',
            $path,
            $m
        )) {
            return strtolower($m[1]);
        }

        $basename = trim((string) basename($path), '/');
        if ($basename === 'data' || $basename === 'thumbnail') {
            $parts = array_values(array_filter(explode('/', trim($path, '/'))));
            if (count($parts) >= 2) {
                return (string) $parts[count($parts) - 2];
            }
        }

        return $basename;
    }

    /**
     * Datei hochladen und an ein Objektfeld anhängen (z. B. Standard-Feld „picture“).
     *
     * @return string UUID der neu angelegten Datei-Ressource
     *
     * @throws \RuntimeException Bei API-Fehler
     */
    public function uploadFileAndAttachToObject(
        string $objectUuid,
        string $contents,
        ?string $filename = null,
        string $fieldKey = 'picture'
    ): string {
        $fileUuid = $this->uploadFile($contents, $filename);
        $this->addFileToObject($objectUuid, $fieldKey, $fileUuid);

        return $fileUuid;
    }

}