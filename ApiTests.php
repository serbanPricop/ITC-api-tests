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
            'base_uri' => 'https://test.itc-benchmarking.edw.ro',
            'verify' => false
        ]);
    }

    public function testGetApiCountries()
    {
        $response = $this->client->get('/api/v1/countries?page=0');
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);
        $results = $data['data'];

        $expected_results = file_get_contents('expected_results_countries.json');
        $json_results = json_decode($expected_results, true);

        $this->assertArrayHasKey('data', $data);

        $this->assertSame($json_results['data'], $results);

    }

    public function testApiBSO()
    {
        $response = $this->client->get('/api/v1/bso-types?page=0');
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);
        $results = $data['data'];

        $expected_results = file_get_contents('expected_results_bso.json');
        $json_results = json_decode($expected_results, true);

        $this->assertArrayHasKey('data', $data);

        $this->assertEquals(sizeof($results),sizeof($json_results['data']));

        foreach($json_results as $json_result){
            $i = 0;
            $this->assertEquals($json_result[$i]['name'],$results[$i]['name']);
            $i++;
        }
    }

    public function testApiInstitutions()
    {
        $response = $this->client->get('/api/v1/institutions');
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);
        $results = $data;

        $total_pages = $results['pager']['total_pages'];

        $all_pages_data = array();
        for($i=0; $i < $total_pages; $i++)
        {
            $res = $this->client->get('/api/v1/institutions?page='.$i);
            $data = json_decode($res->getBody(),true);
            array_push($all_pages_data,$data);

        }
        // $data['data'][page place]['title']
        foreach($all_pages_data as $data)
        {   
            // print_r($items_per_page = sizeof($data[0]));
            
        }

        // $trade_agency = array();
        // foreach($results as $result)
        // {
        //     if($result['title'] == 'Proeksport')
        //     {
        //         $trade_agency = $result;
        //     }
        // }

        // print_r($trade_agency);


        
    }


}