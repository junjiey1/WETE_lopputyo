(function mapModule(window) {
    'use strict';

    function Map() { //mapin konstruktori
        this.map;
        this.vehicles;
        this.markers = [];
        this.routes=[];
        this.apiPath="https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/";
    }

     Map.prototype.initMap = function() { //mapin alustusmetodi
        this.map = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 60.1699,
                lng: 24.9384
            },
            zoom: 15
        });
    }

    //Lisää merkin kartalle
    Map.prototype.setMarker = function setMarker(pos, markerTitle) {
        var marker = new google.maps.Marker({
            position: pos,
            title: markerTitle,
        });
        this.markers.push(marker); //ja työntää sen taulukkoon
        marker.setMap(this.map);
    }

    //luo polylinen kartalle!
    Map.prototype.setPolyLine = function(route,routeTitle) {
      var polyLine = new google.maps.Polyline({
          path: route,
          geodesic: true,
          strokeColor: '#FF0000',
          strokeOpacity: 1.0,
          strokeWeight: 2,
          title: routeTitle
          
      });

      polyLine.setMap(this.map);
      this.routes.push(polyLine);
  }

    Map.prototype.loadRoutes=function(){ //lataa reitit
        $.get(this.apiPath +"routes/",function(result){
            var objects=JSON.parse(result); //parsetaan vastaus
            
            for (var linja=0; linja<objects.length;linja++){
                var tmpRoute=[];
                
                for (var reittipiste=0; reittipiste<objects[linja]["Points"].length;reittipiste++){
                    var tmpPoint={
                        "lat":objects[linja]['Points'][reittipiste]['Lat'],
                        "lng":objects[linja]['Points'][reittipiste]['lng'],
                    };
                    tmpRoute.push(tmpPoint);
                }
                this.setPolyLine(tmpRoute,"Jee");
            }
        })
    }

    window.App = window.app || {};
    window.App.Map=Map; //sijoitetaan luokkan App namespaceen 


}(window))


$(document).ready(function() { //tehdään alustus täällä
    'use strict';
     
     var map=new App.Map();
     map.initMap();
     map.loadRoutes();

})