<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
require('vendor\autoload.php');

class ApiTests extends TestCase{


    private $http;

    protected function setUp() : void
    {
        parent::setUp();
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'https://test.itc-benchmarking.edw.ro/api/v1'
        ]);
    }

    public function testGetApiCountries()
    {
        $response = $this->client->request('GET','/countries?page=0');
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);

        $this->assertHasArrayKey('data', $data);

        foreach($clients as $client){
            $response->assertJson([
                "data" =>[
                    "name"=> $client->name,
                    'field_iso2' => $client->field_iso2,
                    'field_iso3' => $client->field->iso3,
                ]
                ]);
        }

    }

    public function testApiBSO()
    {
        $response = $this->client->request('GET','/bso-types?page=0');
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);

        $this->assertHasArrayKey('data', $data);
        echo $client;
        foreach ($clients as $client){
            $response->assertJson([
                'data' => [
                    'name' =>$client->name
                ]
                ]);
        }
    }

    // public function testApiInstitutions()
    // {
    //     $response = $this->client->get('/institutions');

    // }

}