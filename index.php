<?php

# URI parser helper functions
# ---------------------------

class RestApi{
    
    private $connection=null;
    private $db=null;
    private $collection=null;
    
    private function getResource() {
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

    private function getParameters() {
        # returns an associative array containing the parameters
        $resource = $_SERVER['REQUEST_URI'];
        $param_string = "";
        $param_array = array();
        if (strstr($resource, '?')) {
            # URI has parameters
            $param_string = substr($resource, strpos($resource, '?')+1);
            $parameters = explode('&', $param_string);                      
            foreach ($parameters as $single_parameter) {
                $param_name = substr($single_parameter, 0, strpos($single_parameter, '='));
                $param_value = substr($single_parameter, strpos($single_parameter, '=')+1);
                $param_array[$param_name] = $param_value;
            }
        }
        return $param_array;
    }

    private function getMethod() {
        # returns a string containing the HTTP method
        $method = $_SERVER['REQUEST_METHOD'];
        return $method;
    }
    
    private function dataGetter(){
        
    }
 
# Handlers
# ------------------------------
# These are mock implementations

	private function getVehicles($parameters=null) {
	    
	    //Osoitetaan oikeaan kokoelmaan
	    
	    $this->collection = $this->db->vehicles; //osoittaa vehicles kokoelmaan
            
		# implements POST method for person
		# Example: POST /staffapi/person/id=13&firstname="John"&lastname="Doe"
		$id=urldecode($parameters["id"]);
		
		if ($id=="ALL"){
		    $ajoneuvot=$this->collection->find();
		    $ajoneuvoarray=array();
		    
		     foreach ($ajoneuvot as $kulkuvaline){
                array_push($ajoneuvoarray,$kulkuvaline);
            }
            
            echo json_encode ($ajoneuvoarray);
            http_response_code(200);
		} else {
		    http_response_code(204); # Method not allowed
		}
	}

# Main
# ----
    public function __construct(){
        //luodaan tietokantayhteys
        $this->connection = new MongoClient();
            // select a database
        $this->db = $this->connection->Data;
            
        //haetaan parametrit urlista
	    $resource = $this->getResource();
        $request_method = $this->getMethod();
        $parameters = $this->getParameters();
    
        # Redirect to appropriate handlers.
	    if ($resource[0]=="API") {
    	    if ($request_method=="GET" && $resource[1]=="vehicle") {
        	    $this->getVehicles($parameters);
		    } else {
			    http_response_code(405); # Method not allowed
		    }
	    }
	    else {
		    http_response_code(405); # Method not allowed
	    }
    }
    
    public function __destruct(){
        $this->connection->close();
    }
	
}

    $restApi=new RestApi();
    
?>

