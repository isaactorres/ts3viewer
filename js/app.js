function render(source, object){
	var source = source;
	var template = Handlebars.compile(source);
	return template(object);
}

Handlebars.registerPartial("channels", $("#ts3-channels").html());

Handlebars.registerHelper('getStatus', function(context) {
	var html = "";
	switch(context) {
		//Channel Status
		case "normal":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-channel-green'></span></span>";
		break;
		case "password_protected":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-channel-yellow'></span></span>";
		break;
		case "max_clients":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-channel-red'></span></span>";
		break;
		//User Status
		case "online":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-player-off'></span></span>";
		break;
		case "speaking":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-player-on'></span></span>";
		break;
		case "away":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-away'></span></span>";
		break;
		case "speakers_muted":
		case "speakers_muted_hardware":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-output-muted'></span></span>";
		break;
		case "microphone_muted":
		case "microphone_muted_hardware":
			html = "<span class='ts3-icon-size'><span class='ts3-icon ts3-input-muted'></span></span>";
		break;
	}
	return new Handlebars.SafeString(html);
});

Handlebars.registerHelper('getFlag', function(context) {
	var html = "";
	switch(context) {
		//Channel Flags
		case "default":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-default'></span></span>";
		break;
		case "password_protected":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-register'></span></span>";
		break;
		case "moderated":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-moderated'></span></span>";
		break;
		//User Flags
		case "Server Admin":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-group-300'></span></span>";
		break;
		case "Normal":
			html = "";
		break;
		case "Channel Admin":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-group-100'></span></span>";
		break;
		case "Operator":
			html = "<span class='ts3-icon-size ts3-flag'><span class='ts3-icon ts3-group-200'></span></span>";
		break;
		case "Voice":
			html = "";
		break;
		case "Guest":
			html = "";
		break;
	}
	return new Handlebars.SafeString(html);
});

var json = "json/";

$.getJSON( json, function( json ) {
	var source = $("#ts3-source").html();
	var html = render(source, json);
 	$("#ts3-viewer").append(html);
});