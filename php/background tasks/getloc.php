   <?php
   /**
    * Php script, joka pitää ajastaa käynnistymään puolen tunnin välein!!
    * Tämä scripti päivittää ajoneuvojen sijainnit tietokantaa reaaliajassa ja käynnistyksen
    * yhteydessä lataa reittien tiedot ja pysäkit
    * */
   
  /**
  * Receiver-luokka joka mahdollistaa tiedon hakemisen reittiopas-apista, ja tiedon siirtämisen json muodossa mongodb-tietokantaan
  *
  * Luokkan käyttöä varten sinulla tulee olla asennettuna mongodb ja php connector.
  * Luokan tarkoituksena on siirtää reittiopas-apin tiedot json muodossa omaan tietokantaansa.
  * Luokan käyttäminen luo Data nimisen tietokannan johon se sijoittaa seuraavat kokoelmat:
  * -vehicles
  *     Sisältää hsl-alueen raitiovaunut ja seuraavat tiedot jokaisesta ajoneuvosta(arvot annettu esimerkiksi):
  *         ID: Metro33 //Ajoneuvon yksilöllinen ID
  *         Route: 1001A //reitin tunnus
  *         lng: 24 //koordinaatti
  *         lat: 60 //koordinaatti
  *         Angle:220 //Ajoneuvon kulma
  *         Direction: (1 tai 2) //kumpaa päätepistettä kohti ajoneuvo kulkee
  *         prevStop: 138172 //edellisen pysäkin yksilöllinen ID
  *         currentStop: 128371 //nykyisen pysäkin ID
  *         departure: 2 //paljonko aikaa seuraavalle pysäkille(min)
  *         timestamp: 193781371783 //kertoo milloin tieto on haettu reittiopas apista
  * -stops
  *     {
  *      "ID": "1003%204", //Reitin id
  *      "Dir": 2, //suunta
  *      "Stops": [ //taulukko pysäkeistä
  *          {
  *              "StopId": " 1171444", //yksilöllinen id
  *              "Lat": "60.1925517810368", //koordinaatit
  *              "Lng": "24.9307673446964"
  *          }
  *      ]
  *  }
  * -routes
  * {
  *      "ID": "1010", //linjan tunnus
  *      "Dir": 1, //suunta
  *      "Points": [ //linjan jokaisen reittipisteen koordinaattiparit taulukkona
  *          { //esimerkkipiste
  *              "Lat": "60,1610173901228",
  *              "Lng": "24,9474712157529"
  *          }
  *      ]
  *}
  * -usercars
  * {
  *      "ID": "raobo", //yksilöllinen ID
  *      "Lng": "-335.0701332092285", //koordinaatit
  *      "lat": "60.163205243174495",
  *      "TimeStamp": 1462513927 //aikaleima
  *  },
  *
  *
  * 
  */
    class Receiver{
        
    private $data;
    private $fields=array();
    private $dataStruct;
    
    /**
     * Muuttuja, joka annetaan MongoClientiksi
     */
    private $connection;
    /**
     * Muuttuja, joka osoittaa käytettyyn tietokantaan
     */
    private $db;
    /**
     * Muuttuja, joka osoittaa käytettyyn kokoelmaan
     */
    private $collection;
        
        /**
         * Luokan parametriton konstruktori, joka luo tietokantayhteyden ja tyhjentää olemassaolevan tietokannan.
         */
        public function __construct(){
            
        //variables for datagain
           $this->dataStruct=array();
           $this->fields=array();
           $this->columns=array();
           
           //Mondodb init
           // connect
            $this->connection = new MongoClient();

            // select a database
            $this->db = $this->connection->Data;
            $this->db->dropDatabase();
        }
        
        /**
         * 
         * Luokan destruktori, joka sulkee tietokantayhteyden 
         */
        public function __destruct(){
            $this->connection->close();
        }
        
        /**
         * Metodi, joka hakee reittiopas-apista ratikoiden ja metrojen reaaliaikaiset sijainnit
         * Sijoittaa tiedot Data->vehicles kokoelmaan.
         * Palauttaa false ,jos toiminto epäonnnistuu
         * $returns boolean
         */
        public function getData(){ //Hakee ajoneuvojen sijainnin
            
            $this->db = $this->connection->Data;
            $this->collection = $this->db->vehicles; //soittaa vehicles kokoelmaan
            
            $this->data= file_get_contents("http://83.145.232.209:10001/?type=vehicles&lng1=23&lat1=60&lng2=26&lat2=61&online=1"); //Hakee reittiopas apista liikenteessä olevat linjurit
            
            if (!$this->data){
                return false;
            }
            
            $array=explode("\r\n",$this->data); //Räjäyttää datan taulukoiksi (rivi)
            
            foreach ($array as $rivi){
                $tmparray=array();
                
                $fields=explode(";",$rivi); //jakaa jokaisen ; merkillä erotetun osion omaksi osiokseen
                
                foreach ($fields as $kentta) { //työntää tmparrayhin jokaisen taulukon jäsenen 1;2;3 jne
                    array_push($tmparray, $kentta);
                }
                
                
                if ($tmparray[0]){ //jos ei ole tyhjä eikä null niin voidaan luoda assosiatiivinen taulukko 
                
                    // RHKL00098;1010;24.943583;60.165314;347;2;0;1291404;1023
                    //  ID, route, lat, lng, bearing, direction, prevstop,currentstop, departure
                    $tmparray=array("ID" => $tmparray[0],
                                "Route"=> $tmparray[1],
                                "lng" => $tmparray[2],
                                "lat"=> $tmparray[3],
                                "angle" => $tmparray[4],
                                "direction" => $tmparray[5],
                                "prevStop" => $tmparray[6],
                                "currentStop" => $tmparray[7],
                                "departure" => $tmparray[8],
                                "timestamp" => "".time()
                                );
                                
                        //updaten parametrit query, update, upsert
                        $this->collection->update(array("ID" => $tmparray['ID']), $tmparray, array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti
                }
                
            }
            
            return true;
            
        }
        
        /**
         * Hakee reitin reittiopas apista ja sijoittaa ne tietokantaan.
         * Data->route
         * Palauttaa false jos tiedonhaku epäonnistuu.
         * 
         * @param string $line Linjan ID
         * @param string $dir Linjan suunta (1 tai 2)
         * @returns boolean
         */
        public function getRoute($line="",$dir=1){
            
            $line=str_replace(" ","%20",$line);
            $this->db = $this->connection->Data;
            $this->collection = $this->db->routes; //soittaa vehicles kokoelmaan
            
            
            $data=file_get_contents("http://83.145.232.209:10001/?type=routewgs&line=".$line."&direction=".$dir); //Hakee reittiopas apista liikenteessä olevat linjurit
            $route=array();
            
            
            if (!$data){
                return false;
            }
            
            $data=explode(";",$data);
            
            foreach($data as $sarake){
                
                if ($sarake && $sarake!=""){
                $sarake=explode(":",$sarake);
                
                $tmparray=array("Lat" => $sarake[0],
                                "Lng" => $sarake[1]);
                array_push($route,$tmparray);
                }
            }
            
            $route=array("ID"=>$line,"Dir"=>$dir ,"Points" =>$route);
            //updaten parametrit query, update, upsert
            
            //$this->collection->insert($route);
            $this->collection->update(array("ID" => $route['ID'], "Dir"=>$dir ), $route, array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti($route);
            return true;
        }
        
        /**
         * Hakee reittiopas-apista pysäkin tiedot ja antaa ne tietokantaa
         * data->stops
         * 
         *  @param string $line Linjan ID
         *  @param string $dir Linjan suunta (1 tai 2)
         *  @returns boolean
         * */
        public function getStop($line="",$dir=1){
            $line=str_replace(" ","%20",$line);
            $this->db = $this->connection->Data;
            $this->collection = $this->db->stops; //soittaa vehicles kokoelmaan
            
            
            $data=file_get_contents("http://83.145.232.209:10001/?type=stoplocations&line=".$line."&direction=".$dir); //Hakee reittiopas apista liikenteessä olevat linjurit
            $route=array();
            
            if (!$data){
                return false;
            }
            
            $data=explode("\r\n",$data);
            
            foreach($data as $sarake){
                
                if ($sarake && $sarake!=""){
                $sarake=explode(";",$sarake);
                
                $tmparray=array("StopId" => $sarake[0],
                                "Lat" => $sarake[1],
                                "Lng" => $sarake[2]);
                array_push($route,$tmparray);
                }
            }
            
            $route=array("ID"=>$line,"Dir"=>$dir ,"Stops" =>$route);
            //updaten parametrit query, update, upsert
            
            //$this->collection->insert($route);
            $this->collection->update(array("ID" => $route['ID'], "Dir"=>$dir ), $route, array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti($route);
            return true;
        }
        
        /**
         * Metodi, joka hakee mongodb tietokannasta ajoneuvojen linjatiedot.
         * Metodi hakee jokaisen linjan pysäkit ja routewgs polylinjan
         * Metodi sijoittaa saadut tiedot routes ja stops kokoelmiin
         * */
        
        public function getRoutes(){
            $this->collection = $this->db->vehicles; //osoittaa vehicles kokoelmaan
            $ajoneuvoarray=array();
            
            $ajoneuvot=$this->collection->find();
            
            foreach ($ajoneuvot as $kulkuvaline){
                array_push($ajoneuvoarray,$kulkuvaline['Route']);
            }
            
            $ajoneuvoarray=array_unique ($ajoneuvoarray);
            
            foreach($ajoneuvoarray as $routeid){
                
                for ($suunta=1;$suunta<=2;$suunta++){
                    $this->getRoute($routeid,$suunta);
                    $this->getStop($routeid,$suunta);
                }
                
                
            }
        }
    }
    
    /**
     * 
     * Tämä funktio luo uuden Receiver olion sekä hakee pysäkit ja reitit kerran.
     * Tämän jälkeen puolen tunnin ajan scripti päivittää ajoneuvojen sijaintia reaaliajassa
    */
    function main (){
        $maxTime=30*60;
        $vastaanotin=new Receiver();
        $timeStep=5;
        
        while ($maxTime>=0){
            $vastaanotin->getData();
            $maxTime-=$timeStep;
            sleep($timeStep);
        }
        
        $vastaanotin->getRoutes();
        
       
    }
    
    main();
    
?>
