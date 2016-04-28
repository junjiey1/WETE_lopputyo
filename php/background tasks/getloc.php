   <?php
   
    //Tämä on php scripti joka ajastetaan suoriutumaan joka 2. sekuntti.
    //päivittää tietokantaan jokaisen liikenteessä olevan ajoneuvon tiedot! 
    //crontabilla ajastus
    class Receiver{
    private $data;
    private $fields=array();
    private $dataStruct;
    
    private $connection;
    private $db;
    private $collection;
    
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

        }
        
        public function __destruct(){
            $this->connection->close();
        }
        
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
                                "lat" => $tmparray[2],
                                "lng"=> $tmparray[3],
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
            
            $route=array("Line"=>$line,"Dir"=>$dir ,"Points" =>$route);
            //updaten parametrit query, update, upsert
            
            //$this->collection->insert($route);
            $this->collection->update(array("Line" => $route['Line'], "Dir"=>$dir ), $route, array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti($route);
            return true;
        }
        
        public function getStop($line="",$dir=1){
            $line=str_replace(" ","%20",$line);
            $this->db = $this->connection->Data;
            $this->collection = $this->db->Stops; //soittaa vehicles kokoelmaan
            
            
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
            
            $route=array("Line"=>$line,"Dir"=>$dir ,"Stops" =>$route);
            //updaten parametrit query, update, upsert
            
            //$this->collection->insert($route);
            $this->collection->update(array("Line" => $route['Line'], "Dir"=>$dir ), $route, array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti($route);
            return true;
        }
        
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
                    //$this->getRoute($routeid,$suunta);
                    $this->getStop($routeid,$suunta);
                }
                
                
            }
        }
    }
    
    $vastaanotin=new Receiver();
    $vastaanotin->getData();
    $vastaanotin->getRoutes();
    
?>
