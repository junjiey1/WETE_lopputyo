(function mapModule(window){
    'use strict';
    
    function Map(){
        this.map;
        this.vehicles;
    }
    
    Map.prototype.initMap=function() {
        var helsinki = {lat: 60.1699, lng: 24.9384};
        this.map = new google.maps.Map(document.getElementById('map'), {
          center: helsinki,
          zoom: 15
          
          
        });
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
            }
        }
        
          xhttp.open("GET", "https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/vehicles", true);
          xhttp.send();
    }
    
   
    
    
    
    Map.prototype.setMarker = function setMarker(){
        
        
            var helsinki = {lat: 60.1699, lng: 24.9384};
            
            var marker = new google.maps.Marker({
            position: helsinki,
            title:"Hello World!"
            });
            marker.setMap(this.map);
    }
    
  
  
    
    
    
    
    window.App=window.app || {};
    window.App.map=new Map();
    
    window.App.map.initMap();
    window.App.map.getSynch();
    window.App.map.setMarker();
    
}(window))