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

        $agencies_data = $this->get_all_pages_data($all_pages_data);

        $all_agencies = $this->get_all_agencies($agencies_data);
        $italian_agency = array();
        foreach($all_agencies as $agency)
        {
            if($agency['title'] == 'Italian Trade Agency')
            {
                $italian_agency = $agency;
            }
        }

        $agency_changed = explode("T",$italian_agency['changed'])[0];
        $agency_country_id = $italian_agency['country']['id'];
        $agency_country_iso2 = $italian_agency['country']['iso2'];
        $agency_country_iso3 = $italian_agency['country']['iso3'];
        $agency_country_bso_type = $italian_agency['bso_types'][0]['id'];

        $italian_agency_params = array($italian_agency['id'],$agency_changed,$agency_country_id,$agency_country_iso2,$agency_country_iso3,$agency_country_bso_type);
        $response_changed = $this->client->get('/api/v1/institutions?changed='.$agency_changed);
        $this->assertEquals(200,$response_changed->getStatusCode());
        $data_changed = json_decode($response->getBody(),true);
        $result_changed = $data_changed;
        print_r($result_changed);
    }

    public function get_all_pages_data($all_pages){

        $all_agencies_data = array();

        for($i = 0;$i < sizeof($all_pages);$i++)
        {
            array_push($all_agencies_data, $all_pages[$i]['data']);
        }

        return $all_agencies_data;
    }

    public function get_all_agencies($all_agencies_data)
    {   
        $all_agencies = array();
        for($i = 0; $i < sizeof($all_agencies_data); $i++)
        {
            foreach($all_agencies_data[$i] as $agency)
            {
                array_push($all_agencies,$agency);
            }

        }

        return $all_agencies;
    }
}