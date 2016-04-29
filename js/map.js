


$(document).ready(function(){
    
}

function initMap() {
  var map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: 60.192059, lng: 24.945831},
    zoom: 12,
    styles: [{
      featureType: 'poi',
      stylers: [{ visibility: 'off' }]  // Turn off points of interest.
    }, {
      featureType: 'transit.station',
      stylers: [{ visibility: 'on' }]  // Turn off bus stations, train stations, etc.
    }],
    disableDoubleClickZoom: true
  });
  
}


function getRoute(){
    var url="php/getloc.php";
    var pyynto=new XMLHttpRequest();
    var sijainnit;
    
    pyynto.onreadystatechange=function(){
        if (pyynto.readyState ==4 && pyynto.status==200){
            sijainnit=pyynto.responseText;
            console.log(sijainnit);
        }
        
        
    }
    
    pyynto.open('get', url,true);
    pyynto.send();
}

getRoute();
