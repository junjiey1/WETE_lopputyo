
    $(document).ready(function(){
        
        getSync();
        //lataa kaikki ajoneuvot kartalle
    });
    
    function initialize() {
    var fenway = {lat: 42.345573, lng: -71.098326};
    var map = new google.maps.Map(document.getElementById('map'), {
    center: fenway,
    zoom: 14
     });
    var panorama = new google.maps.StreetViewPanorama(
      document.getElementById('loc'), {
        position: fenway,
        pov: {
          heading: 34,
          pitch: 10
        }
      });
    map.setStreetView(panorama);
    
}


   function getSync() {
        var xhttp=new XMLHttpRequest();
        
        xhttp.onreadystatechange=function(){
            if (xhttp.readyState == 4 && xhttp.status == 200) {
                 $('#Otsikko').val(xhttp.responseText); //tässä vastaus
            }
        }
        
          xhttp.open("GET", "https://bussitutkakoulutyo17813173171261263-kapuofthe.c9users.io/API/vehicle?id=ALL", true);
          xhttp.send();
   }
   
   
        
