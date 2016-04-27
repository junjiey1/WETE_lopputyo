   <?php
    class Receiver{
    private $data;
    private $fields=array();
    private $dataStruct=array();
    
    private $connection;
    private $db;
    
        public function __construct(){
           $this->fields=array();
           $this->columns=array();
           $this->connection = new MongoClient();
           $this->db = $connection->hslDatabase;
           $this->collection = $db->information;
        }
        
        public function __destruct(){
            $this->connection->close();
        }
        
        public function getData(){
            $this->data= file_get_contents("http://83.145.232.209:10001/?type=vehicles&lng1=23&lat1=60&lng2=26&lat2=61&online=1");
            
            $array=explode("\r\n",$this->data);
            
            foreach ($array as $rivi){
                $tmparray=array();
                
                $fields=explode(";",$rivi);
                
                foreach ($fields as $kentta) {
                    array_push($tmparray, $kentta);
                }
                
                if ($tmparray[0]!=""){
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
                                
                array_push($this->dataStruct,$tmparray);
                }
                
            }
            
            echo json_encode($this->dataStruct);

           
        }
        
    }
    
    
    $vastaanotin=new Receiver();
    $vastaanotin->getData();
    
   
    
    
?>
