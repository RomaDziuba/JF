document.getElementById("block").style.display = 'none';


function show(text, evt) {

evt = (evt) ? evt : event;

    if (evt) {


var elem = document.getElementById("block");

  elem.innerHTML = text;

  elem.style.display = 'block';

  elem.style.position = 'absolute';


  var coords = getEventCoords(evt);

  elem.style.left = coords.left;

  elem.style.top = coords.top;


    }

}


function getEventCoords(evt) {

    var coords = {left:0, top:0};

    if (evt.pageX) {

        coords.left = evt.pageX;

        coords.top = evt.pageY;

    } else if (evt.clientX) {

        coords.left = evt.clientX + document.body.scrollLeft - document.body.clientLeft;

        coords.top = evt.clientY + document.body.scrollTop - document.body.clientTop;

        if (document.body.parentElement && document.body.parentElement.clientLeft) {

            var bodParent = document.body.parentElement;

            coords.left += bodParent.scrollLeft - bodParent.clientLeft;

            coords.top += bodParent.scrollTop - bodParent.clientTop;

        }

    }

    return coords;


}

function hide() {

 var elem = document.getElementById("block").style;

 elem.display = 'none';

}

