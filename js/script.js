'use strict';
(function mapModule(window) {


    function Map() { //mapin konstruktori
        this.map;
        this.vehicles;
        this.markers = [];
        this.routes = [];
        this.routeData = [];
        this.apiPath = "https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/";
        this.followID=null;

        this.initMap = function() { //mapin alustusmetodi

            this.map = new google.maps.Map(document.getElementById('map'), { //uusi kartta
                center: { //helsinki keskipusteeksi
                    lat: 60.1699,
                    lng: 24.9384
                },
                zoom: 15

            });
       
        }
        
        this.update=function(){ //kutsutaan tietyn väliajan välein
            this.getVehicles();
            this.getUsers();
            this.center();
        }
        
        this.center=function(){

            var tmpPoint=null;
            
            if (this.followID){
                for (var indeksi in this.markers){
                    if (this.markers[indeksi]['title']==this.followID){
                        tmpPoint=this.markers[indeksi];
                        break;
                    }
                }
                this.map.panTo({lat:tmpPoint['lat'],lng:tmpPoint['lng']});
            }
        }

        this.setMarker = function(pos, markerTitle) { //luodaan uusi merkki
             var mIcon;
             var me=this;
             var img = { 
                 url:'http://users.metropolia.fi/~junjiey/stop_3.png' ,
                 size: new google.maps.Size(40, 55),
                 origin: new google.maps.Point(0, 0),
                 anchor: new google.maps.Point(0, 50)

             };
            for (var indeksi=0;indeksi<this.markers.length;indeksi++){ //Tarkistetaan ettei ole olemassa samannimistä
                if (markerTitle==this.markers[indeksi]['title']){
                    this.markers[indeksi].setPosition(pos); //jos on niin päivitetään sijaintia
                    return false;
                }
            }
            
            if (markerTitle.search("STOP")>=0){ //jos on bussipysäkki niin asetetaan musta nuoli kuvakkeeksi
                mIcon=img;
                //{ path: google.maps.SymbolPath.BACKWARD_OPEN_ARROW,
                  //      scale: 2}
            } else if (markerTitle.search("usercars")>=0){
                mIcon={ path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                    strokeColor: '#00FF00'}
            } else {
                mIcon="";
            }
            
            var marker = new google.maps.Marker({ //luodaan markeri
                position: pos,
                title: markerTitle,
                icon:mIcon
            });
      
            if (markerTitle.search("HSL")>=0){
                marker.addListener('click',function(){
                    me.followID=this['title'];
                });
            }
            
            this.markers.push(marker); //ja työntää sen taulukkoon
            marker.setMap(this.map); //asetetaan kartalle
        }

        this.setPolyLine = function(route, routeTitle) { //piirtää reitin kartalle
            var polyLine = new google.maps.Polyline({
                path: route,
                geodesic: true,
                strokeColor: '#FF00FF',
                strokeOpacity: 1.0,
                strokeWeight: 2,
                title: routeTitle

            });

            polyLine.setMap(this.map);
            this.routes.push(polyLine);
        }
        
       
    
        this.getUsers=function(){
            var me=this;
            var title;
            
             $.get(this.apiPath + "usercars/", function(result) { //ajax

                var objects = JSON.parse(result); //parsetaan vastau
               
                for (var ajoneuvo = 0; ajoneuvo < objects.length; ajoneuvo++) {
                    
                    var auto = objects[ajoneuvo];

                    if (auto['lat'] && auto['Lng']) {
                        var tmpPoint = {
                            "lat": parseFloat(auto['lat'].replace(",", ".")),
                            "lng": parseFloat(auto['Lng'].replace(",", ".")),
                        };
                        title=auto['ID'];
                        me.setMarker(tmpPoint,title+"usercars");
                    }
                };

            });
        }

        this.getRoutes = function() {
            var me = this;

            $.get(this.apiPath + "routes/", function(result) {

                var objects = JSON.parse(result); //parsetaan vastaus

                for (var linja = 0; linja < objects.length; linja++) {
                    var tmpRoute = [];
                    var title = "";

                    for (var reittipiste = 0; reittipiste < objects[linja]["Points"].length; reittipiste++) {

                        var tmpPoint = {
                            "lat": parseFloat(objects[linja]['Points'][reittipiste]['Lat'].replace(",", ".")),
                            "lng": parseFloat(objects[linja]['Points'][reittipiste]['Lng'].replace(",", ".")),
                        };
                        title = objects[linja]['ID'] + "," + objects[linja]['Dir'] + ":Route"
                        tmpRoute.push(tmpPoint);
                    }
                    me.setPolyLine(tmpRoute, title);

                };

            });

        }

        this.getStops = function() {
            var me = this;

            $.get(this.apiPath + "stops/", function(result) {

                var objects = JSON.parse(result); //parsetaan vastau


                for (var linja = 0; linja < objects.length; linja++) {
                    
                    //käydään objektit läpi
                    for (var reittipiste = 0; reittipiste < objects[linja]["Stops"].length; reittipiste++) {

                        var osoitettava = objects[linja]['Stops'][reittipiste];
                        //selkeytetään koodia
                        
                        //tarkistaa datan eheyttä
                        if (osoitettava['Lat'] && osoitettava['Lng']) {
                            var tmpPoint = {
                                "lat": parseFloat(osoitettava['Lat'].replace(",", ".")),
                                "lng": parseFloat(osoitettava['Lng'].replace(",", ".")),
                            };
                        }
                        
                        //asettaa merkin kartalle
                        me.setMarker(tmpPoint, osoitettava['StopId']+"STOP");
                    }

                };

            });

        }


        this.getVehicles = function() {
            var me = this;
            var title="";

            $.get(this.apiPath + "vehicles/", function(result) { //ajax

                var objects = JSON.parse(result); //parsetaan vastau

                for (var ajoneuvo = 0; ajoneuvo < objects.length; ajoneuvo++) {
                    
                    var auto = objects[ajoneuvo];

                    if (auto['lat'] && auto['lng']) {
                        var tmpPoint = {
                            "lat": parseFloat(auto['lat'].replace(",", ".")),
                            "lng": parseFloat(auto['lng'].replace(",", ".")),
                        };
                        title=auto['ID'];
                        me.setMarker(tmpPoint,title+"HSL");
                    }
                };

            });

        }
        
        this.addMe=function(pos){
            var message=null;
            
            if (localStorage['ID'] && localStorage['password'] ){
               
            } else {
                localStorage['ID']=Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
                localStorage['password']=Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
            }
            
             message={ID: localStorage['ID'],
                        password: localStorage['password'],
                        Lat: pos.coords.latitude,
                        Lng: pos.coords.longitude}
                        
            $.post(this.apiPath+'usercars/', message);
        }


    }
    


    window.App = window.app || {};
    window.App.Map = Map; //sijoitetaan luokkan App namespaceen 


}(window))


$(document).ready(function() { //tehdään alustus täällä
    'use strict';

    var map = new App.Map(); //luodaan mappi, peruspävitykset ja asetetaan paivitys
    map.initMap();
    map.getRoutes();
    map.getStops();
    
    window.setInterval(function (){
        map.update();
    },3000);
    
    $("#LOCATE").click(function(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos){
                map.addMe(pos);
            });
        } else {
            alert("Sijaintia ei saatu haettua");
        }
    })

})