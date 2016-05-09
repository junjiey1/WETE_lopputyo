<?php

/**
 * Tiedosto, joka määrittelee API-luokan ja kutsuu luokan konstruktoria
 */
 
 /**
  * RestApi on luokka, joka ylläpitaa kahta apia:
  *     Rest-api
  *     Hakuapia
  */
class RestApi
{
    /**
     * Muuttuja yhteydelle
     */
    private $connection = null;
    /**
     * Muuttuja, joka osoittaa tietokantaan
     */
    private $db = null;
    /**
     * Ḿuuttuja, joka osoittaa kokoelmaan
     */
    private $collection = null;
    /**
     * Taulukko, joka sisältää tietokannassa olevien kokoelmien nimet!
     */
    private $collections=null;
    
    /////////////////////////////////////////////////////////////////////////////////////////////
    //apumetodit url-osoitteen käsittelyyn
    
    /**
     * Metodi, joka hakee / merkillä erotetut resurssit taulukkoon url-osoitteesta
     * esim http:url.fi/api/vehicles/
     * Palauttaa taulukon ["api","vehicles"]
     * @returns array numeerisesti indeksoidun taulukon url-osista
     */
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
    /**
     * metodi, joka palauttaa assisiatiivisen taulukon joka sisältää url-osoitteen parametrit
     * @returns assioatiivisen taulukon url-osoitteen parametreistä
     */
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
    
    /**
     * @returns string palauttaa käytetyn http-metodin
     */
    private function getMethod()
    {
        # returns a string containing the HTTP method
        $method = $_SERVER['REQUEST_METHOD'];
        return $method;
    }
    
    /**
     * Poistaa parametrina annetusta muuttujasta kaikki erikoismerkit paitsi pisteen
     * @param mixed &$param Viittaus muuttujaan
     * @return &$param 
     */
    private function check (&$param){
        $param=preg_replace('/[^A-Za-z0-9\.]/', '', $param);
        
    }
    
 
    
    /**
     * Metodi, joka hakee Tietokannasta annetulla assosiatiivisella taulukolla dokumentteja ja tulostaa ne echolla.
     * Palauttaa myös kyselyn tulosta kuvaavan http koodin.
     * 
     * @param array $parameters assosiatiivinen taulukko jolla haetaan tietoa mongodb-tietokannasta
     * @param string $collection Kokoelman nimi, josta tietoa haetaan
     */
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
    
    
  
    /**
     * Metodi joka päivittää tietokannassa olevia dokumentteja. Jos ei ole olemassa parametreja täyttävää dokumenttia niin uusi luodaan.
     *
     * @param array $findQuery Assosiatiivinen taulukko (katso mongodv find)
     * @param array $updateValues Arvot jotka päivitetään tai sijoitetaan tietokantaan
     * @param string kokoelma, jonne haku ja sijoitus tehdään
     */
    private function SetData($findQuery = null,$updateValues=null,$collection=""){ 
        
        if ($collection!="" && $findQuery && $updateValues){ //Jos annettu parametrit
            $this->collection = $this->db->$collection; //haluttuun kokoelmaan
            $this->collection->update($findQuery, $updateValues, array("upsert"=> TRUE)); //Syötetään tiedot tietokantaan!
        }
        
    }
    
    /**
     * 
     * Metodi jota kutsutaan kun tulee post-metodi osoitteeseen /API/usercars/
     * Odottaa, että post-metodin mukana tulevat seuraavat parametrit: ID, password, Lng, Lat
     * Jos tietokannasta ei löydy samalla nimellä ID tä niin luo uuden ajoneuvon post parametreilla.
     * Post metodi oikeilla tunnistetiedoilla päivittää ajoneuvon sijaintia
     * Jos ajoneuvoa ei pävitetä puoleen tuntiin niin se poistetaan tietokannasta
     */
    
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


    /**
     * Luokan konstruktori
     * 
     * Tekee seuraavat asiat:
     * luo tietokantayhteyden,
     * Hakee tietokannasta kokoelmien nimet,
     * tarkistaa kaikki saadut parametrit ja tulostaa niiden perusteella käyttäjälle tietoja tai päivittää lomakkeita tietokannasta
     */
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
    
    /**
    * Destruktori
    * 
    * Sulkee luokan tietokantayhteyden
    */
    public function __destruct()
    {
        $this->connection->close();
    }
    
}

/**
 * Funktio, joka luo RestApi-luokan
 */
function main(){
    $restApi = new RestApi();
}

main();


?>
