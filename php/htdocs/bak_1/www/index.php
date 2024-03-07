<?php
	require_once('com/moserv/net/url.php');

	

	$url = new Url();



	header("Location: {$url->toString(Url::TOK_PORT)}/qr/000");


	header('Content-Type: application/xhtml+xml; charset=utf-8');
?><?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html id="html" xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Hopespot</title>
</head>
<style>
/* <![CDATA[ */

img#pictureUrl {
	width: 40%;
	margin: 0px auto;
	display: block;
	border-radius: 50%;
	border: 3px solid gray;
	box-shadow: 5px 5px 10px grey;
}


/* ]]> */
</style>
<!--<script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>-->
<script src="https://static.line-scdn.net/liff/edge/versions/2.22.4/sdk.js"></script>
<script>
/* <![CDATA[ */


const share = async () => {
		let x = await liff.shareTargetPicker([
			{
				type: 'text',
				text: 'Hello, World!'
			},
		], {isMultiple: false});

		console.log(JSON.stringify(x));
		
}


document.addEventListener('DOMContentLoaded', (event) => {


	(() => {
		liff.init({liffId: "1657161914-j417lv7K" }).then(() => {
			if (!liff.isLoggedIn()) {
				liff.login();
			}
			else {
				liff.getProfile().then((profile) => {
					document.getElementById("pictureUrl").setAttribute("src", profile.pictureUrl);
					document.getElementById("userId").append(profile.userId);
					document.getElementById("displayName").append(profile.displayName);
					document.getElementById("statusMessage").append(profile.statusMessage);
					document.getElementById("email").append(liff.getDecodedIDToken().email);
				});
			}
		})
		.catch((err) => {
			alert('err');
		});
	})();

	document.getElementById("logoutButton").addEventListener("click", (event) => {
//		liff.openWindow({
//			url: "https://line.me",
//			external: true
//		});
		liff.closeWindow();
	});

	document.getElementById("btn-share").addEventListener("click", (event) => {
//		let x = liff.shareTargetPicker([
//			{
//				type: 'text',
//				text: 'Hello, World!'
//			},
//		], {isMultiple: false}).then((t) => {
//			alert("yes " + JSON.stringify(t));
//		}).catch((res) => { alert('fail');});

		share();
	});
});


/* ]]> */
</script>
<body>
Jirapat 😊KOB
<img id="pictureUrl" />
<p id="userId"><b>userId:</b> </p>
<p id="displayName"><b>displayName:</b> </p>
<p id="statusMessage"><b>statusMessage:</b> </p>
<p id="email"><b>email:</b> </p>
<button id="logoutButton" src="btnLogOut" style="display: none;">Log Out</button>
<button id="btn-share">share</button>
</body>
</html>
