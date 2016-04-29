<?php

# URI parser helper functions
# ---------------------------

class RestApi
{
    
    private $connection = null;
    private $db = null;
    private $collection = null;
    
    /////////////////////////////////////////////////////////////////////////////////////////////
    //apumetodit url-osoitteen kösittelyyn
    
    private function getResource()
    {
        # returns numerically indexed array of URI parts
        $resource_string = $_SERVER['REQUEST_URI'];
        if (strstr($resource_string, '?')) {
            $resource_string = substr($resource_string, 0, strpos($resource_string, '?'));
        }
        $resource = array();
        $resource = explode('/', $resource_string);
        array_shift($resource);
        return $resource;
    }
    
    private function getParameters()
    {
        # returns an associative array containing the parameters
        $resource     = $_SERVER['REQUEST_URI'];
        $param_string = "";
        $param_array  = array();
        if (strstr($resource, '?')) {
            # URI has parameters
            $param_string = substr($resource, strpos($resource, '?') + 1);
            $parameters   = explode('&', $param_string);
            foreach ($parameters as $single_parameter) {
                $param_name               = substr($single_parameter, 0, strpos($single_parameter, '='));
                $param_value              = substr($single_parameter, strpos($single_parameter, '=') + 1);
                $param_array[$param_name] = $param_value;
            }
        }
        return $param_array;
    }
    
    private function getMethod()
    {
        # returns a string containing the HTTP method
        $method = $_SERVER['REQUEST_METHOD'];
        return $method;
    }
    
    private function dataGetter()
    {
        
    }
    /////////////////////////////////////////////////////////////////////////////////////////////
    
    // Rest-apin toiminnalisuus
    private function getVehicles($parameters = null,$collection="")
    {
        //luodaan taulukko jonne tietokantakysely luodaan
        $ajoneuvoarray = array();
         $id = urldecode($parameters["id"]); //haetaan ajoneuvon ID
        
        
        //jos parametrit on annettu
        if (strlen($collection)>0) { 
            
             //Osoitetaan oikeaan kokoelmaan
             $this->collection = $this->db->$collection; //osoittaa vehicles kokoelmaan
        
            if ($id==""){  //tarkistetaan, minkä id:n käyttäjä on asettanut!!!!
                $ajoneuvot = $this->collection->find(); //Haetaan mondodbstä kaikki ajoneuvot
            } else {
                $ajoneuvot = $this->collection->find(array("ID"=>$id)); //haetaan tietty ajoneuvo
            }
            

            if ($ajoneuvot->count() > 0) { //Jos ajoneuvoja
                
                //työnnetään vastausket taulukkoon
                foreach ($ajoneuvot as $kulkuvaline) {
                    array_push($ajoneuvoarray, $kulkuvaline);
                }
                //Muunnetaan jsoniksi taulukko
                echo json_encode($ajoneuvoarray);
                http_response_code(200); //Onnistuu
                
            } else {
                http_response_code(200);
            }
        } else {
            http_response_code(204); # ei sisaltoa
        }
    }
    
   //////////////////////////////////////////////////////////////////////////////////////////////////
    //Konstruktori    
    public function __construct()
    {
        //luodaan tietokantayhteys
        $this->connection = new MongoClient();
        // select a database
        $this->db         = $this->connection->Data;
        
        //haetaan parametrit urlista
        $resource       = $this->getResource();
        $request_method = $this->getMethod();
        $parameters     = $this->getParameters();
        
        
        //siivotaan ne!
        //poistetaan erikoismerkit
        $resource=preg_replace('/[^A-Za-z0-9\-]/', '', $resource);
        $request_method=preg_replace('/[^A-Za-z0-9\-]/', '', $request_method);
        $parameters=preg_replace('/[^A-Za-z0-9\-]/', '', $parameters);
        
        
        echo $resource[0] . $request_method .$resource[1] == "vehicle";
        //Ohjataan pyynnot parametrien perusteella oikeisiin paikkoihin
        if ($resource[0] == "API") { //Apin tunnus
            if ($request_method == "GET" && $resource[1] == "vehicle") { //jos metodi on get ja "luokka" vehicle
                $this->getVehicles($parameters); //Käytetään metodia getVehicle!
            } else {
                http_response_code(405); # Method not allowed
            }
        } else {
            http_response_code(405); # Method not allowed
        }
    }
    
    
    ///////////////////////////////////////////////////////////////////////////////
    //destruktori
    public function __destruct()
    {
        $this->connection->close();
    }
    
}

//Tynkä joka luo luokan!
$restApi = new RestApi();

?>
