<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;

class ApiTests extends TestCase{

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

        $all_agencies = [];
        for($i=0; $i < $total_pages; $i++)
        { 
            $res = $this->client->get('/api/v1/institutions?page='.$i);
            $data = json_decode($res->getBody(),true);
            $all_agencies = array_merge($all_agencies, $data['data']);
        }

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

        $this->verify_params_request_country('country',$agency_country_id,'id');
        $this->verify_params_request_country('country_iso2',$agency_country_iso2,'iso2');
        $this->verify_params_request_country('country_iso3',$agency_country_iso3,'iso3');

        $response_id = $this->client->get('/api/v1/institutions?id='.$italian_agency['id']);
        $this->assertEquals(200,$response_id->getStatusCode());
        $data_id = json_decode($response_id->getBody(),true);
        $result_id = $data_id;

        $all_id_agencies_data = $this->get_page_data($result_id);    
        $all_id_agencies = $this->get_all_agencies($all_id_agencies_data);

        $this->match_search($all_id_agencies, $italian_agency['id']);

        $response_bso_type = $this->client->get('/api/v1/institutions?bso_type='.$agency_country_bso_type);
        $this->assertEquals(200,$response_bso_type->getStatusCode());
        $data_bso_type = json_decode($response_bso_type->getBody(),true);
        $result_bso_type = $data_bso_type;
        

        $result_pages_bso_type = $result_bso_type['pager']['total_pages']-1;
        $pages_bso_type = $this->make_request_with_param($result_pages_bso_type,'bso_type=',$agency_country_bso_type);
        $all_bso_types_agencies_data = $this->get_all_pages_data($pages_bso_type);
        $all_bso_types_agencies = $this->get_all_agencies($all_bso_types_agencies_data);
        $match_bso_type = 0;

        foreach($all_bso_types_agencies as $bso_type_agencies)
        {
            if($bso_type_agencies['bso_types'][0]['id'] == $agency_country_bso_type)
            {
                $match_bso_type = 1;
            }
        }
        $this->assertEquals($match_bso_type,1);
        

        $response_changed = $this->client->get('/api/v1/institutions?changed='.$agency_changed);
        $this->assertEquals(200,$response_changed->getStatusCode());
        $data_changed = json_decode($response_changed->getBody(),true);
        $result_changed = $data_changed;
        

        $result_pages_changed = $result_changed['pager']['total_pages']-1;
        $pages_changed = $this->make_request_with_param($result_pages_changed,'changed=',$agency_changed);
        $all_changed_agencies_data = $this->get_all_pages_data($pages_changed);
        $all_changed_agencies = $this->get_all_agencies($all_changed_agencies_data);

        $this->match_search($all_changed_agencies, $italian_agency['id']);

        $final_response = $this->client->get('/api/v1/institutions?page=0&changed='.$agency_changed.'&country='.$agency_country_id.'&country_iso2='.$agency_country_iso2.'&country_iso3='.$agency_country_iso3.'&bso_type='.$agency_country_bso_type.'&id='.$italian_agency['id']);
        $this->assertEquals(200,$final_response->getStatusCode());
        $final_data = json_decode($final_response->getBody(),true);
        
        $expected_final_results = file_get_contents('italian_agency.json');
        $json_final_results = json_decode($expected_final_results, true);

        $this->assertArrayHasKey('data', $final_data);

        $this->assertSame($json_final_results, $final_data);

    }

    public function get_all_pages_data($all_pages){

        $all_agencies_data = array();

        for($i = 0;$i < sizeof($all_pages);$i++)
        {
            array_push($all_agencies_data, $all_pages[$i]['data']);
        }

        return $all_agencies_data;
    }

    public function get_page_data($page)
    {
        $pages_data = array();

        for($i = 0;$i < sizeof($page);$i++)
        {
            array_push($pages_data, $page['data']);
        }

        return $pages_data;

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
    

    public function make_request_with_param($pages, $param, $agency_param)
    {
        $result = array();
        for($i=0; $i <= $pages; $i++)
        { 
            $res = $this->client->get('/api/v1/institutions?page='.$i.'&'.$param.$agency_param);
            $data = json_decode($res->getBody(),true);
            array_push($result,$data);

        }

        return $result;

    }

    public function verify_params_request_country($query, $param, $array_query)
    {
        $response = $this->client->get('/api/v1/institutions?'.$query.'='.$param);
        $this->assertEquals(200,$response->getStatusCode());
        $data = json_decode($response->getBody(),true);
        $result = $data;

        $all_agencies_data = $this->get_page_data($result);
        
        $all_agencies = $this->get_all_agencies($all_agencies_data);
        $match = 0;
        foreach($all_agencies as $agency)
        {
            if($agency['country'][$array_query] == $param)
            {
                $match = 1;
            }
        }
        $this->assertEquals($match,1);

    }

    public function match_search($all_agencies, $param)
    {
        $match = 0;
        foreach($all_agencies as $agency)
        {
            if($agency['id'] == $param)
            {
                $match = 1;
            }
        }
        $this->assertEquals($match,1);
    }

}