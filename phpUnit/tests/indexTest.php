<?php

spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class indexTest extends PHPUnit_Framework_TestCase
{
    private $base="https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/";
    
    private $respCode200=array("API",
                                "API/",
                                "API/vehicles/",
                                "API/stops/",
                                "API/routes",
                                "API/usercars",
                                "SEARCH/vehicles/",
                                "SEARCH/routes/",
                                "SEARCH/stops/");
    
    private $respCode204=array("API/vehicles/jeejee",
                              "API/vehicles/------++");
                                
    private $respCode405=array("TEST//////////////",
                                "Wohoo/",
                                "FAKEAPI",
                                "Notworking/");
                                
    private $connection = null;
    private $db = null;
    private $collection = null;
    
    public function __construct(){
        parent::__construct();
        $this->connection = new MongoClient();
        $this->db  = $this->connection->Data;
    }
    

    
    private function urlTest($url,$method,$response) //apumetodi, joka tarkistaa palauttaako api oikean paluuarvon
    {
      @file_get_contents($this->base.$url);
      
      if ($http_response_header[0]==$response){
          return true;
      } else {
          return false;
      }
      
    }
    
    private function post($url,$data){ //luo post-kyselyn!

    //Luodaan sisältö postiin
    $options = array(
            'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
            )
        );
        
        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
        
        if ($http_response_header[0]==$response){
          return true;
      } else {
          return false;
      }
        
    }
    
    
    public function testUrls(){ //Testi joka testaa palvelimen paluuarvot
        
        for ($indeksi=0;$indeksi<count($this->respCode200);$indeksi++){ //testaa että lista palauttaa oikean koodin!
           $this->assertEquals($this->urlTest($this->respCode200[$indeksi],"GET","HTTP/1.1 200 OK"),true);
        }
        
        for ($indeksi=0;$indeksi<count($this->respCode405);$indeksi++){ //testaa että lista palauttaa oikean koodin!
           $this->assertEquals($this->urlTest($this->respCode405[$indeksi],"GET","HTTP/1.1 405"),false);
        }
        
    }
    
    
    public function testCollectionAmount(){ //testaa että neljä kokoelmaa
        $this->assertEquals(count($this->db->getCollectionNames()),4);
    }
    
    public function testDataParsing(){ //testaa että kaikilla sivuilla on validia jsonia
        for ($indeksi=0;$indeksi<count($this->respCode200);$indeksi++){
            json_decode(file_get_contents($this->base.$this->respCode200[$indeksi]));
            $this->assertEquals ((json_last_error() == JSON_ERROR_NONE),true);
        }
    }
    
    public function TestpostCar(){
        
    }
    
}
