(function mapModule(window){
    'use strict';
    
    function Map(){
        this.map;
        this.vehicles;
        this.markers=[];
    }
    
    Map.prototype.initMap=function() {
        var helsinki = {lat: 60.1699, lng: 24.9384};
        this.map = new google.maps.Map(document.getElementById('map'), {
          center: helsinki,
          zoom: 15
        });
        
        
        window.App.map.setMarker(pos, markerTitle);
        //window.setTimeout(window.App.map.setMarker(), 5000);
     }
     
     
     
     Map.prototype.getSynch=function getSync() {
        var xhttp=new XMLHttpRequest();
        
        xhttp.onreadystatechange=function(){
            if (xhttp.readyState == 4 && xhttp.status == 200) {
                 console.log(xhttp.responseText);
                 this.vehicles=JSON.parse(xhttp.responseText);
                 
                 for (var x in this.vehicles){
                     console.log(x[0].lng);
                 }
                 
                
                 //hae täällä sijainnit ja kutsu tarvittava määrä setMarkereita
                 //setmarker({lng: 833, lat: 179732},"Otsikko");
                 for(var i=0; i<this.vehivles.length; i++){
                     var latx = this.vehicles[i]["lat"];
                     var lngy = this.vehicles[i]["lng"];
                     var pos= new google.map.LatLng(latx , lngy);
                     var markerTitle = this.vehicles[i]["ID"];
                     Map.prototype.setMarker(pos, markerTitle);
                 } 
                 
               
            }
        }
        
          xhttp.open("GET", "https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/vehicles", true);
          xhttp.send();
    }
    
   
    
    
    
    Map.prototype.setMarker = function setMarker(pos, markerTitle){
            var marker = new google.maps.Marker({
                position: pos,
                title:markerTitle,
                });
            this.markers.push(marker);    
            marker.setMap(this.map);
    }
    
  

    
    
    
    window.App=window.app || {};
    window.App.map=new Map();
    window.App.map.initMap();
    
    
}(window))