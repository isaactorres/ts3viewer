TS3 Viewer
=========
Yet another TeamSpeak 3 Viewer, now with JSON!

Benefits of TS3 Viewer
-----------
* your ts3 server is rendered in both JSON and HTML
* minimal configuration to get up and running
* easy front end customization

Dependencies and credit
-----------
TS3 Viewer depends on
* [jQuery]
* [Handlebars.js]

and has code based on 
* TSStatus by [Sebastien Gerard]

Installation
--------------
* download TS3 Viewer to your webserver
* edit config.php with your ts3 server settings
* browse to the json folder to view your ts3 server rendered as a JSON object
* browse to the index to view your ts3 server rendered in html
* customize!!!

Options
--------------
* Switch between the colored TS3 icons and mono TS3 icons by linking to the appropriate css file
```sh
<link rel="stylesheet" type="text/css" href="css/ts3_default_colored_2014.css">
```
```sh
<link rel="stylesheet" type="text/css" href="css/ts3_default_mono_2014.css">
```
* Increase and decrease the icon sizes by editing the width and height of the icon-size class   
```sh
#ts3-viewer .ts3-icon-size {
	width: 16px;
	height: 16px;
	display:inline-block;
	vertical-align: baseline;
}
```   

License
----
* NYS - Class D
* **Free Software!!**

[Sebastien Gerard]:http://tsstatus.sebastien.me
[Handlebars.js]:http://handlebarsjs.com
[jQuery]:http://jquery.com