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
            
            $this->collection = $this->db->vehicles; //soittaa vehicles kokoelmaan
            
            $this->data= file_get_contents("http://83.145.232.209:10001/?type=vehicles&lng1=23&lat1=60&lng2=26&lat2=61&online=1"); //Hakee reittiopas apista liikenteessä olevat linjurit
            
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
                                "departure" => $tmparray[8]
                                );
                                
                        //updaten parametrit query, update, upsert
                        $this->collection->update(array("ID" => $tmparray['ID']), $tmparray,array("upsert"=> TRUE)); //Jos on olemassa jo niin päivitetään, muuten luodaan uusi dokumentti
                    
                }
                
            }
            
        }
        
    }
    
    
    $vastaanotin=new Receiver();
    $vastaanotin->getData();
    
    
    
?>
