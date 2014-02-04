/*
 * livemap version 0.13
 * 
 * (reusable version)
 * 
 * requires: [google maps], [jquery]
 * 
 */

// FIXME: global ?
/*
function livemap_object_static( data ) {
	livemap_object.actual_sync( data );
}
*/

function live_map(options) {
	this.__init({
		name: "livemap",
		div_map: "livemap_id",
		div_admin: "admin_panel",
		project: "",
		lat: 0,
		lng: 0,
		map: null,
		infowindow: null,
		first_run: true,
		edit_url: "http://tools.voanews.com/utilities/livemap/",
		icon_url: "http://www.voanews.com/MediaAssets2/projects/fidget/",
		json_url: "http://www.voanews.com/MediaAssets2/projects/fidget/",
		
		auth: { a: 0, b: 0, c: false },
		cursor: [ ],	// temp, so don't need id
		markers: { },	// perm, need id
		shouts: { }		// perm, need id
	});
	this.__init(options);
}

live_map.prototype.__init = function(options) {
	for( k in options ) {
		this[k] = options[k];
	}
}

live_map.prototype.try_login = function() {

	if( this.auth.c == true ) return( false );
	this.auth.c = true;
	
	$.cookie(this.name, "true" );
	
	$("#" + this.div_admin).html(
		"<div style='padding:10px; margin-top:10px; margin-bottom:10px; background-color: cornflowerblue; color: white'>" +
		"<button onclick='livemap_object.try_logout(); return(false);'>{logout}</button>" +
		"<button style='margin-left:10px' onclick='livemap_object.add_point(); return(false);'>{add point}</button>" +
		"&nbsp;&nbsp;Tweet URL: <input id='add_shout_url' type='text' value='' />" +
		"<button style='margin-left:10px' onclick='livemap_object.insert_shout(); return(false);'>{add shout}</button>" +
		"</div>"
	);

}

live_map.prototype.try_logout = function() {
	$.cookie(this.name, null );
	window.location = window.location;
}

live_map.prototype.actual_sync = function( that, data ) {
	for( i = 0; i < data.list.length; i++ ) (function( data, i ) {
		
		if( data.destination == 'Top' ) {
			if( typeof that.markers[data.id] == 'undefined' ) {
				that.add_marker( data, i );
			}
		} else {
			if( typeof that.shouts[data.id] == 'undefined' ) {
				// that.add_shout( data, i );
			}
		}
		
	})(data.list[i], i);
	
	for( i = 0; i < data.deleted.length; i++ ) (function( data, i ) {
		that.delete_object( data, i );
	})(data.deleted[i], i);
}

live_map.prototype.delete_object = function( data, i ) {
	for( var marker in this.markers ) {
		if( data.id == marker ) {
			
			if( typeof this.markers[data.id] != 'undefined' ) {
				
				// delete object
				this.markers[data.id].setMap( null );
				
			}
		}
	}
}

live_map.prototype.sync = function() {
	
	if( this.auth.c == true ) {
		var s_url = this.edit_url + "edit.php?action=list&callback=?";
	} else {
		// FIXME: this url
		var s_url = this.json_url + "inauguration.json?callback=?&callback=livemap_object_static&ts=" + Math.random();
	}

	try {
		jQuery.getJSON(
			s_url, null, function(data) {
				this.actual_sync( data );
			}
		);
	} catch( err ) {
	
	}
}

live_map.prototype.init = function() {
	
/*	// secret combination, like in james bond
	$("#footerServices").click( function() { livemap_object.auth.a = 1; livemap_object.try_login(); });
	$("#footerLinks .inner").click( function() { livemap_object.auth.b = 1; livemap_object.try_login(); });

	// guess we could've placed this style inline. :/
	$("#inauguration_map").css({
		'margin-top':'20px',
		'margin-bottom':'20px'
	}).html( "" );
*/
	// bugfix, thanks @dtoybo
	try {
		document.getElementById(this.div_map).addEventListener(
			"MozMousePixelScroll", function (event) {
				event.preventDefault();
			}, false
		);
	} catch( err ) {
	
	}

	// ginit!
	this.map = new google.maps.Map(
		document.getElementById(this.div_map), {
		zoom: 15,
		center: new google.maps.LatLng(this.lat, this.lng),
		mapTypeId: google.maps.MapTypeId.ROADMAP,	// TERRAIN
		nothing: null,
		scrollwheel: false
		//panControl: false,
		//draggable: false
	});

	this.infowindow = new google.maps.InfoWindow();

//	$("div.twitter").css( { "background-color": "white" } );
//	$("<div id='fake_tooltip_holder' style='position:absolute; width:0px; height:0px'></div>").insertBefore($("div.twitter"));
//	$("#fake_tooltip_holder").html(
//		'<div style="position:relative; top:200px; left:100px" id="fake_tooltip_pos">' +
//		'<img src="http://www.voanews.com/MediaAssets2/projects/fidget/tooltip-right.png" style="position:absolute; opacity:0.9;filter: alpha(opacity=90); " />' +
//		'<div style="position:absolute; padding-left:30px; padding-top:20px; width:340px; height:170px;" id="fake_tooltip_text"></div>' + 
//		'</div>'
//	);
	
	// load all active gpoints
	this.sync();
	
	// $("#fake_tooltip_holder").fadeOut("slow");
	
	// check every minute, or so
	var that = this;
	setInterval( function() {
		that.sync();
	}, 60000 );

/*
  setInterval( function() {
		livemap_object.show_random_shout();
	}, 30000 );
*/
	// dirty fix.....
	// setInterval( function() { livemap_object.center_shout(); }, 1000 );
	
}

live_map.prototype.request_delete = function(delete_id) {
	$("#request_delete_id_" + delete_id).animate( { opacity: 0 }, 300 );
	$("#confirm_delete_id_" + delete_id).animate( { opacity: 1 }, 300 );
}

live_map.prototype.confirm_delete = function(delete_id) {
	jQuery.getJSON(
		this.edit_url + "edit.php?action=delete&id=" + delete_id + "&callback=?", null, function(data) {
			livemap_object.sync();
		}
	);
}

live_map.prototype.delete_fragment = function(delete_id) {
	return(
		"<a style='opacity:1' id='request_delete_id_" + delete_id + "' href='javascript:livemap_object.request_delete(" + delete_id + ")'>{delete # " + delete_id + "}</a>" +
		"<a style='opacity:0' id='confirm_delete_id_" + delete_id + "' href='javascript:livemap_object.confirm_delete(" + delete_id + ")'>{really?}</a><br/>"
	);
},

//returns a small snippet for delete / confirm delete
live_map.prototype.add_marker = function( data, i ) {

	// permanent object
	this.markers[data.id] = new google.maps.Marker({
		position: new google.maps.LatLng(data.lat, data.lng),
		map: this.map,
		icon: this.icon_url + 'map-' + data.provider + '.png'
	});

	// have to wrap this in anon :/
	var that = this;
	
	(function(marker) {
		google.maps.event.addListener(marker, 'click', function() {
			that.infowindow.setContent(
				//data.id + " = " + data.embed
				(that.auth.c ? that.delete_fragment(data.id) : '<br/>') + data.embed
			);
			that.infowindow.open( that.map, marker );
			
			try {
				twttr.widgets.load();
			} catch( err ) {
			
			}
		});
	})(this.markers[data.id]);

}



var livemap_object = {
	
/*
	insert_shout: function( data, i ) {
		var url = $("#add_shout_url").val();

		jQuery.getJSON(
			"http://tools.voanews.com/utilities/inauguration-2013/edit.php?action=add&type=shout&url=" + url + "&callback=?", null, function(data) {
				$("#id_add_shout_text").val("");
			}
		);
	},
	add_shout: function( data, i ) {
		this.shouts[data.id] = data;
	
		// this.shout( data, data.embed, "left", Math.random()*580, 200 + (Math.random()*70) );
	},
	center_shout: function() {
		$("#fake_tooltip_text").css( { 'margin-top': "0px" } );

		// vert balance?, 180px
		var h = $("#fake_tooltip_text .twitter-tweet-rendered").height();
		var d = 180 - h;
		$("#fake_tooltip_text").css( { 'margin-top': (d/2) + "px" } );	
	},
	random_shout: function() {
		var counter = 0;
		for( var r in livemap_object.shouts ) {
			counter++;
		}
		
		var random = parseInt(Math.random() * counter);

		var counter2 = 0;
		for( var r in livemap_object.shouts ) {
			if( counter2 == random ) return( livemap_object.shouts[r] );
			counter2++;
		}
	},
	show_random_shout: function() {
		var t = livemap_object.random_shout();
		livemap_object.shout( t, t.embed, "left", Math.random()*580, 200 + (Math.random()*70) );	
	},
	shout: function( data, brute_string, direction, x, y ) {
		$("#fake_tooltip_pos").css({ top: y, left: x });
		$("#fake_tooltip_holder").fadeIn("slow");
		
		$("#fake_tooltip_text").html(
			brute_string +
			(livemap_object.auth.c ? ('<br/>' + livemap_object.delete_fragment(data.id)) : '')
		);
	},
	*/
	save_point: function( which_one, q_url ) {
		jQuery.getJSON(
			"http://tools.voanews.com/utilities/inauguration-2013/edit.php?action=add&url=" + q_url + "&callback=?", null, function(data) {
				// hide current cursor, sync
				
				this.remove_temp( which_one );
				this.sync();
			}
		);
	},
	try_adding: function( which_one ) {

		var url = $("#inauguration_text_" + which_one).val();
		var q_url = 
			"&url=" + escape(url) + 
			"&lat=" + livemap_object.cursor[which_one].getPosition().lat() +
			"&lng=" + livemap_object.cursor[which_one].getPosition().lng();
		
		jQuery.getJSON(
			"http://tools.voanews.com/utilities/inauguration-2013/discover.php?=" + q_url + "&callback=?", null, function(data) {
				livemap_object.infowindow.setContent(
					data.entry.embed.code +
					"<hr/>Everything ok?  " +
					"<a href='javascript:livemap_object.save_point(" + which_one + ", \"" + q_url + "\")'>{save}</a> " +
					"<a href='javascript:livemap_object.remove_temp(" + which_one + ")'>{Cancel}</a> "
				);
				
				try {
					twttr.widgets.load();
				} catch( err ) {
				
				}
			}
		);
		
	},
	add_point: function() {

		// create a temporary marker that you can edit, save, or delete (hide)
		this.cursor.push( new google.maps.Marker({
			position: new google.maps.LatLng(38.892302,-77.026391),
			map: livemap_object.map,
			title: "Click to edit, Click+Drag to move",
			draggable: true,
			bounce: true
		}));

		// have to wrap this in anon :/
		(function( marker, num) {
			google.maps.event.addListener(marker, 'click', function() {
				
				marker.__num = num;
			
				livemap_object.infowindow.setContent(
					"<div style='font-size:10px'>" +
					"{<a href='javascript:livemap_object.remove_temp(" + num + ")'>cancel</a>} " +
					"URL: <input style='' autocomplete='off' value='' spellcheck='false' type='text' id='inauguration_text_" + num + "' /> " +
					"{<a href='javascript:livemap_object.try_adding(" + num + ");'>Discover</a>}" +
					"<div class='placeholder' style='border-top:1px dotted gray; border-bottom:1px dotted gray'></div>" +
					"<div class='placeholder_after'></div>" +
					"</div>"
				);
				livemap_object.infowindow.open( livemap_object.map, marker );
			});
		})(this.cursor[this.cursor.length-1], this.cursor.length-1);
	},
	remove_temp: function( which_one ) {
		livemap_object.cursor[which_one].setMap( null );
	},

}

// lets be nice and wait
//$(document).ready( function() {
//	
//	if( $.cookie("inauguration_map_auth") == "true" ) {
//		livemap_object.auth.a = 1;
//		livemap_object.auth.b = 1;
//		livemap_object.try_login();
//	}
//	
//	livemap_object.init();
//});

//

function round_ts( ts, min ) {
	return( Math.floor(ts/(60*min*1000))*(60*min*1000) );
}
