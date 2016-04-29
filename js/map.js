var helsinki = {lat: 60.172059, lng: 24.945831};

function CenterControl(controlDiv, map) {

       
        var controlUI = document.createElement('div');
        controlUI.style.backgroundColor = '#fff';
        controlUI.style.border = '1px solid #fff';
        controlUI.style.borderRadius = '3px';
        controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
        controlUI.style.cursor = 'pointer';
        controlUI.style.marginBottom = '22px';
        controlUI.style.textAlign = 'center';
        controlUI.title = 'Click to recenter the map';
        controlDiv.appendChild(controlUI);

        
        var controlText = document.createElement('div');
        controlText.style.color = 'rgb(25,25,25)';
        controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
        controlText.style.fontSize = '16px';
        controlText.style.lineHeight = '38px';
        controlText.style.paddingLeft = '5px';
        controlText.style.paddingRight = '5px';
        controlText.innerHTML = 'Back to Helsinki';
        controlUI.appendChild(controlText);

      
        controlUI.addEventListener('click', function() {
          map.setCenter(helsinki);
        });

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
  

 var centerControlDiv = document.createElement('div');
        var centerControl = new CenterControl(centerControlDiv, map);

        centerControlDiv.index = 1;
        map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);
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
