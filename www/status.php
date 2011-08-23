<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Stenobot status</title>
	<link type="text/css" rel="stylesheet" href="style.css" />
	<script type="text/javascript">
	function ajaxupdate() {
		var xmlhttp;
		
		if (window.XMLHttpRequest) {
			xmlhttp=new XMLHttpRequest();
		} else {
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
//				data=xmlhttp.responseXML.documentElement.getElementsByTagName("STATUS");
//				for (i=0;i<data.length;i++) {
//					document.getElementById("speaking").innerHTML=data[i].nodeValue;
//				}
				getxmlfield(xmlhttp, "speaking");
				getxmlfield(xmlhttp, "chair");
				getxmlfield(xmlhttp, "clerk");
//				document.getElementById("speaking").innerHTML=xmlhttp.responseText;
		}	}
		
		xmlhttp.open("GET","ajax.php?page=status",true);
		xmlhttp.send();
		
		// update every 5 seconds
		setTimeout("ajaxupdate()",5000);
	}
	
	function getxmlfield(xmlhttp, fieldname) {
		document.getElementById(fieldname).innerHTML=xmlhttp.responseXML.documentElement.getElementsByTagName(fieldname)[0].childNodes[0].nodeValue;
	}
	
	ajaxupdate();
	</script>
</head>
<body>

<div>Currently speaking: <span id="speaking">none</span></div>
<div>Chair: <span id="chair">none</span></div>
<div>Clerk: <span id="clerk">none</span></div>

</body>
</html>