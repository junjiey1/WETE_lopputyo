(function mapModule(window){
    'use strict';
    var map;
    
    function Map(){
        this.map;    
        this.vehicles;
        this.markers=[];
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
    
    
    
}(window))


$(document).ready(function(){
    
initMap=function (){
         map = new google.maps.Map(document.getElementById('map'), {
          center: {lat: 60.1699, lng:24.9384 },
          zoom: 15
        });
    }
    initMap();
    
})

  
