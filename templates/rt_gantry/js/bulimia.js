/* This file will hold all of the random js code / functions that may be needed for the bulimia page.
 * Page authored by John
 * Page authored on 15MAR2014
 */

function changeVid(id, vidName) {

	var x = "";
	switch(vidName)
	{
	case "bul_vid":
		x = "http://www.bing.com";
	//	document.getElementById("filmLink").style.border="2px red solid";
		document.getElementById("trailerLink").style.border="0px red solid";
		document.getElementById("behindLink").style.border="0px red solid";
		
	//	document.getElementById("aFilm").style.paddingTop="11px";
		document.getElementById("aTrail").style.paddingTop="12px";
		document.getElementById("aBehind").style.paddingTop="12px";
		break;
	case "bul_trail":
jQuery("#bul_Player").hide();
		x = "https://www.youtube.com/embed/raPd5tVtyaA";
	//	document.getElementById("filmLink").style.border="0px red solid";
		document.getElementById("trailerLink").style.border="2px red solid";
		document.getElementById("behindLink").style.border="0px red solid";
	
	//	document.getElementById("aFilm").style.paddingTop="12px";
		document.getElementById("aTrail").style.paddingTop="11px";
		document.getElementById("aBehind").style.paddingTop="12px";
		break;
	case "bul_bts":
jQuery("#bul_Player").hide();
		x = "https://www.youtube.com/embed/vYZCY2svCPo";
	//	document.getElementById("filmLink").style.border="0px red solid";
		document.getElementById("trailerLink").style.border="0px red solid";
		document.getElementById("behindLink").style.border="2px red solid";
	
	//	document.getElementById("aFilm").style.paddingTop="12px";
		document.getElementById("aTrail").style.paddingTop="12px";
		document.getElementById("aBehind").style.paddingTop="11px";
		break;
	}

//	document.getElementById(id).style.visibility="hidden";
	document.getElementById(id).src=x;
jQuery("#bul_Player").show();
//	$(#bul_Player).onload= "function onLoadFunc() { document.getElementById("+id+").style.visibility=\"visible\";}";
}
