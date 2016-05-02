<?php

# URI parser helper functions
# ---------------------------

//todo!!!!
//tee apiin ominaisus jolla voi hakea avain-arvoparilal itse vehicles?dir=50;

class RestApi
{
    
    private $connection = null;
    private $db = null;
    private $collection = null;
    private $collections=null;
    
    /////////////////////////////////////////////////////////////////////////////////////////////
    //apumetodit url-osoitteen käsittelyyn
    
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
    

    
    private function check (&$param){
        $param=preg_replace('/[^A-Za-z0-9\.]/', '', $param);
        
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////
    
    // Rest-apin toiminnalisuus hakujen suhteen!
    private function getData($parameters = null,$collection="")
    {
        //luodaan taulukko jonne tietokantakysely luodaan
        $ajoneuvoarray = array();
        $dontGet=array("password"=>0,"_id"=>0);
        
        //jos parametrit on annettu
        if (strlen($collection)>0) { 
            
             //Osoitetaan oikeaan kokoelmaan
             $this->collection = $this->db->$collection; //haluttuun kokoelmaan
        
            if (count($parameters)==0){  //tarkistetaan, minkä id:n käyttäjä on asettanut!!!!
                $ajoneuvot = $this->collection->find(array(),$dontGet); //Haetaan mondodbstä kaikki kentät
            } else {
                $ajoneuvot = $this->collection->find($parameters,$dontGet); //haetaan hakuparametrilla Kentästä joka annettiin parametrina
            }
        
            if ($ajoneuvot->count() > 0) { //Jos tuloksia haulla
                
                //työnnetään vastausket taulukkoon
                foreach ($ajoneuvot as $kulkuvaline) {
                    array_push($ajoneuvoarray, $kulkuvaline);
                }
                //Muunnetaan jsoniksi taulukko
                echo json_encode($ajoneuvoarray);
                http_response_code(200); //Onnistuu
                
            } else {
                http_response_code(204); //ei sisaltoa;
            }
        } else {
            http_response_code(404); # virhe
        }
    }
    
    
    //Metodi jolla tietokantaan päivitetään tai syötetään dataa!
    private function SetData($findQuery = null,$updateValues=null,$collection=""){ 
        
        if ($collection!="" && $findQuery && $updateValues){ //Jos annettu parametrit
            $this->collection = $this->db->$collection; //haluttuun kokoelmaan
            $this->collection->update($findQuery, $updateValues, array("upsert"=> TRUE)); //Syötetään tiedot tietokantaan!
        }
        
    }
    
    //Updatemetodi omien ajoneuvojen lisäämiseen!
    
    private function updateCars(){
        
        $this->collection = $this->db->usercars;
        $params=$_POST;
        $sameID=0;
        $passwordMatch=0;
        
        if (gettype ($params)=="array" && count($params)<5){
            
            foreach ($params as $avain=>$arvo){
                $this->check($avain);
                $this->check($arvo);
            }
            
             $id=array("ID"=>$params['ID']); //luodaan käsky idhaulle
             $idAndPw=array("ID"=>$params['ID'],"password"=>$params['password']); //id ja salasanan combo
             $all=array_merge($idAndPw,array("Lng"=>$params['Lng'],"lat"=>$params['Lat'],"TimeStamp"=>time())); //yhdistetään sijainti, id ja salasana
            
             $sameID= $this->collection->find($id)->count(); //onko jo id olemassa
             $passwordMatch= $this->collection->find($idAndPw)->count(); //onko salasana oikein?
            
             if (strlen($params['ID'])>=5){     //jos vähintään 5 merkkiä käyttäjänimessä
                if ($sameID==1){ //Päivitetään olemassaolevaa
                    if ($passwordMatch==1){ //jos salasana on oikein
                        $this->SetData($id,$all,"usercars"); //pävitetään sijaintia
                         http_response_code(200);
                     } else {
                        http_response_code(401); //epaonnistui!
                     }
                 } else { //luodaan uusi
                  $this->SetData($id,$all,"usercars"); //luodaan uusi auto
                     http_response_code(200);
                }
             }
           
        }
    }
    
   //////////////////////////////////////////////////////////////////////////////////////////////////
    //Konstruktori    
    public function __construct()
    {
        //luodaan tietokantayhteys
        $this->connection = new MongoClient();
        // select a database
        $this->db  = $this->connection->Data;
        
        $this->collections=$this->db->getCollectionNames(); //hakee tietokannan kokoelmat!
        
        
        //haetaan parametrit url-osoitteesta (get)
        $resource       = $this->getResource();
        $request_method = $this->getMethod();
        $parameters     = $this->getParameters();
        
        //siivotaan ne!
        //poistetaan erikoismerkit
        $this->check($resource);
        $this->check($request_method);
        $this->check($parameters);
        
        
        foreach($parameters as $title=>$value){ //käydään vielä taulukko läpi
             $this->check($title);
             $this->check($value);
             
             if (strlen($title)==0){
                $parameters=null;
                break;
             }
        }
        
        
        //NON-REST API
        //Ohjataan pyynnot parametrien perusteella oikeisiin paikkoihin
        if ($resource[0] == "SEARCH") { //hakuapi
            if ($request_method == "GET" && in_array($resource[1],$this->collections)) { //jos haettu kokoelma on tietokannassa
                $this->getData($parameters,$resource[1]); //haetaan tiedot parametreilla
            }
        } 
        
        
        if ($resource[0] == "API"){ //rest api!
            if ($request_method == "GET" && in_array($resource[1],$this->collections) ){ //etsii löytyyko haluttua luokkaa kokoelmasta
            
                if (count($resource)>2 && $resource[2]!="" ){ //Jos luokka on annettu ja perässä vielä parametri
                    $this->getData(array("ID"=>$resource[2]),$resource[1]); //haetaan annetulla parametrilla
                } else {
                    $this->getData(null,$resource[1]); //Muuten näytetään kaikki
                }

            } else if ($request_method == "POST" && $resource[1]=="usercars"){//jos apille tulee post metodi usercars tietueeseen!
                $this->updateCars();
                
            } else { //annetaan käytettävissä olevat luokat
                    echo json_encode(array("Available classes" =>$this->collections));
                    http_response_code(200);
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
