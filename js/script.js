'use strict';

/**
 * Moduuli, joka pitää sisällään Map luokan
 * 
 */

(function mapModule(window) {

    /**
     * Map-luokan konstruktori
     * 
     */
    function Map() { //mapin konstruktori
        /**
         * Google maps-apin antama kartta
         */
        this.map;
        /**
         * Kartalla olevat ajoneuvot
         */
        this.vehicles;
        /**
         * Taulukko google maps Marker olioille
         */
        this.markers = [];
        /**
         * Taulukko, jonne apilta saadut reittipisteet säilötään
         */
        this.routes = [];
        /**
         * Rest-apin polku!
         */
        this.apiPath = "https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/";
        /**
         * Seurattavan ajoneuvonID
         */
        this.followID = null;

        /**
         * Alustaa google maps kartan ja asettaa sen keskipisteeksi Helsingin keskustan
         */
        this.initMap = function() { //mapin alustusmetodi

            this.map = new google.maps.Map(document.getElementById('map'), { //uusi kartta
                center: { //helsinki keskipusteeksi
                    lat: 60.1699,
                    lng: 24.9384
                },
                zoom: 15

            });

        }

        /**
         *  metodi, jota kutsutaan muutaman sekunnin välein. Kutsuu getVehicles(), getUsers() ja center() metodeita
         */

        this.update = function() { //kutsutaan tietyn väliajan välein
            this.getVehicles();
            this.getUsers();
            this.center();
        }

        /**
         * Metodi joka keskittää kartan valitun ajoneuvon kohdalle. EI TOIMI VIELÄ!
         */
        this.center = function() {

                var tmpPoint = null; //väliaikainen muuttuja joka laitetaan osoittamaan haluttuun markeriit

                if (this.followID) { //jos followID!=null
                    for (var indeksi in this.markers) { //Käy taulukon läpi
                        if (this.markers[indeksi]['title'] == this.followID) { //jos followId vastaa merkin IDtä
                            tmpPoint = this.markers[indeksi]; //tmpPoint laitetaan osoittamaan merkkiin
                            break; //poistutaan!
                        }
                    }
                    //laitetaan kartta tmpPointerin kohtaan
                    this.map.panTo({
                        lat: tmpPoint['lat'],
                        lng: tmpPoint['lng']
                    });
                }
            }
            /**
             *Luo kartalle uuden merkin.
             * @param {object} pos Olion pitää sisältää seuraavat arvot: {Lat: xxx, lng:xxx}
             * @param {string} markerTitle Kartalle sijoitettavan merkin otsikko
             * @returns boolean Palauttaa true jos uusi luotiin
             */
        this.setMarker = function(pos, markerTitle) { //luodaan uusi merkki
            var mIcon;
            var me = this; //luodaan muuttuja joka osoittaa luokkaan.

            //ladataan kuvakkeet
            var img_stop = {
                url: 'https://preview.c9users.io/kapuofthe/bussitutkakoulutyo17813173171261263/image/stop_4.png?_c9_id=livepreview5&_c9_host=https://ide.c9.io',
                size: new google.maps.Size(40, 55),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(0, 50)

            };
            var img_tram = {
                url: 'https://preview.c9users.io/kapuofthe/bussitutkakoulutyo17813173171261263/image/tram_1.png?_c9_id=livepreview7&_c9_host=https://ide.c9.io',
                size: new google.maps.Size(40, 55),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(0, 50)

            };

            var img_human = {
                url: 'https://preview.c9users.io/kapuofthe/bussitutkakoulutyo17813173171261263/image/human_icon_1.png?_c9_id=livepreview4&_c9_host=https://ide.c9.io',
                size: new google.maps.Size(40, 55),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(0, 50)
            }

            //Tarkistetaan ettei ole olemassa samannimistä
            for (var indeksi = 0; indeksi < this.markers.length; indeksi++) {
                if (markerTitle == this.markers[indeksi]['title']) {
                    this.markers[indeksi].setPosition(pos); //jos on niin päivitetään sijaintia
                    return false; //ja palautetaan false
                }
            }

            //määritetään mikä kuvake piirretään!
            if (markerTitle.search("STOP") >= 0) { //jos on bussipysäkki niin asetetaan musta nuoli kuvakkeeksi
                mIcon = img_stop;

            } else if (markerTitle.search("usercars") >= 0) {
                mIcon = img_human;
            } else {
                mIcon = img_tram;
            }


            var marker = new google.maps.Marker({ //luodaan markeri
                position: pos,
                title: markerTitle,
                icon: mIcon
            });

            this.markers.push(marker); //ja työntää sen taulukkoon
            marker.setMap(this.map); //asetetaan kartalle
        }

        /**
         * Piirtää kartalle reitin polylineilla
         * @param {object} route Google mapsin hyväksymä path objekti
         * @param {string} routeTitle Reitin otsikko!
         */
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


        /**
         * Piirtää kartalle käyttäjän sijainti
         */
        this.getUsers = function() {
            var me = this;
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
                        title = auto['ID'];
                        me.setMarker(tmpPoint, title + "usercars");
                    }
                };

            });
        }

        /**
         * Hae reittiä APIn kautta ja piirtä kartalle reitin polylineilla
         */
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
            /**
             * Hae pysäkkien tiedot APIn kautta ja luo kantalle merkki
             */

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
                        me.setMarker(tmpPoint, osoitettava['StopId'] + "STOP");
                    }

                };

            });

        }

        /**
         * Hae kulkuneuvojen tiedot APIn kautta ja luo kantalle merkki
         */
        this.getVehicles = function() {
            var me = this;
            var title = "";

            $.get(this.apiPath + "vehicles/", function(result) { //ajax

                var objects = JSON.parse(result); //parsetaan vastau

                for (var ajoneuvo = 0; ajoneuvo < objects.length; ajoneuvo++) {

                    var auto = objects[ajoneuvo];

                    if (auto['lat'] && auto['lng']) {
                        var tmpPoint = {
                            "lat": parseFloat(auto['lat'].replace(",", ".")),
                            "lng": parseFloat(auto['lng'].replace(",", ".")),
                        };
                        title = auto['ID'];
                        me.setMarker(tmpPoint, title + "HSL");
                    }
                };

            });

        }

        this.addMe = function(pos) {
            var message = null;

            if (localStorage['ID'] && localStorage['password']) {

            } else {
                localStorage['ID'] = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
                localStorage['password'] = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
            }

            message = {
                ID: localStorage['ID'],
                password: localStorage['password'],
                Lat: pos.coords.latitude,
                Lng: pos.coords.longitude
            }

            $.post(this.apiPath + 'usercars/', message);
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

    window.setInterval(function() {
        map.update();
    }, 3000);

    $("#LOCATE").click(function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                map.addMe(pos);
            });
        } else {
            alert("Sijaintia ei saatu haettua");
        }
    })

})