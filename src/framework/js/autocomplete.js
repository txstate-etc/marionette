/*** public constructor ***/
// options parameters:
// {
//   callback: function(row, tbox) { /* this row was chosen by user, place it into the textbox */ },
//   sources: [  // may also specify 'source' and skip the array
//     {
//       // CURRENTVAL will be replaced with textbox.value
//       query: 'http://your.full.path/to/service?search=CURRENTVAL',
//       handler: function(responseJSON) { /* return an array of row objects with 'value' or 'display' properties, or both */ },
//       json: true // force server's response to be treated as JSON
//     }
//   ],
//   maxrows: 10 // maximum number of rows to display - defaults to 25,
//   xhrrelay: 'xhrrelay.php?url=SOURCEURL' // SOURCEURL will be replaced
// }
//
// EXAMPLE USAGE:
// new autocomplete("mytextbox", {
//   callback: function(row, tbox) { tbox.value = row.username; $('lastnamebox').value = row.lastname; },
//   source: {
//     query: 'https://secure.its.txstate.edu/iphone/people/json.pl?q=CURRENTVAL&n=8',
//     handler: function(responseJSON) { 
//                var res = responseJSON.results;
//                var ret = [];
//                for (var i = 0; i < res.length; i++) {
//                  res[i].display = res[i].lastname+', '+res[i].firstname;
//                  ret.push(res[i]);
//                }
//                return ret;
//              },
//     json: true
//   },
//   xhrrelay: 'xhrrelay.php?url=SOURCEURL'
// });
function autocomplete(id, options) {
	if (!options) options = {};
	this.id = id;
	this.sources = [];
	this.items = [];
	this.xhrlist = [];
	this.hilited = -1;
	this.maxrows = options.maxrows ? options.maxrows : 25;
	this.xhrrelay = options.xhrrelay;
	if (typeof options.callback != 'function') {
		this.finalcallback = function (row) {
			$(this.id).value = $A(row) instanceof Array ? $A(row)[0] : (row instanceof Object ? row.value : row);
		};
	} else {
		this.finalcallback = options.callback;
	}
	if (options.sources instanceof Array) {
		for (var i = 0; i < options.sources.length; i++) this.addsource(options.sources[i].query, options.sources[i].handler, options.sources[i].json);
	} else if (options.source instanceof Object) {
		this.addsource(options.source.query, options.source.handler, options.source.json);
	}
	document.observe('dom:loaded', this.init.bind(this));
}

/*** public methods ***/
// optional method to add additional sources of information
// entries from multiple sources will be concatenated in order of fastest
// response time
autocomplete.prototype.addsource = function(query, handler, json) {
	if (typeof handler != 'function') {
		handler = function (ob) {
			if (ob instanceof Array) return ob;
			else { 
				var rows = ob.split(/\r?\n/);
				var ret = [];
				for (var i = 0; i < rows.length; i++)	ret.push({value: rows[i]});
				return ret;
			}
		};
	}
	if (!json) json = false;
	this.sources.push({q: query, h: handler, json: json});
};

// optional method for specifying the 'callback' option after the constructor
autocomplete.prototype.setcallback = function(cback) {
	this.finalcallback = cback;
};

/*** private methods ***/
// initialize - make sure to re-use as many objects as possible
autocomplete.prototype.init = function() {
	var ac = this;
	if (autocomplete.initiated) { 
		ac.div = autocomplete.div; 
	} else {
		ac.div = $(document.createElement('div'));
		ac.div.id = 'activesearch';
		ac.div.setStyle({position: 'absolute', border: '1px solid #999999', backgroundColor: '#FFFFFF', padding: '3px 0px' });
		ac.div.hide();
		document.body.appendChild(ac.div);
		autocomplete.div = ac.div;
		autocomplete.initiated = true;
	}
	var tbox = $(ac.id);
	tbox.writeAttribute("autocomplete", "off");
	
	// hide when we lose focus
	tbox.observe('blur', ac.abort.bind(ac));
	
	// watch for special keys
	var skipfetch = false
	tbox.observe('keydown', function(e) {
		skipfetch = false;
		if (e.keyCode == 40 || e.keyCode == 9) { // down arrow or tab, respectively
			if (!ac.div.visible()) return;
			ac.shiftactive(1);
			skipfetch = true;
			e.stop();
		} else if (e.keyCode == 38) { // up arrow
			if (!ac.div.visible()) return;
			ac.shiftactive(-1);
			skipfetch = true;
			e.stop();
		} else if (e.keyCode == 13) { // enter or return
			if (!ac.div.visible()) return;
			ac.dofill(ac.items[ac.hilited], tbox);
			skipfetch = true;
			e.stop();
		} else if (e.keyCode == 37 || e.keyCode == 39) { // left and right arrows
			skipfetch = true;
		} else if (e.keyCode == 27) { // escape
			tbox.blur();
		}
	});

	// any other keypress
	tbox.observe('keyup', function () { if (!skipfetch) ac.fetchresults(tbox); });
	tbox.observe('focus', ac.fetchresults.bind(ac, tbox));
	tbox.observe('click', ac.fetchresults.bind(ac, tbox));
}

autocomplete.prototype.shiftactive = function (increment) {
	var ac = this;
	if (!ac.div.down('div', ac.hilited+increment)) { if (increment < 0) ac.resetactive(); else ac.makeactive(0); return; }
	ac.makeactive(ac.hilited+increment);
};

autocomplete.prototype.makeactive = function (idx) {
	var ac = this;
	var newhilite = ac.div.down('div', idx);
	if (!newhilite) return;
	if (ac.active) ac.active.setStyle({backgroundColor: '#FFFFFF'});
	ac.active = newhilite.setStyle({backgroundColor: '#00ccff'});
	ac.hilited = idx;
}

autocomplete.prototype.resetactive = function (idx) {
	var ac = this;
	ac.hilited = -1;
	if (ac.active) ac.active.setStyle({backgroundColor: '#FFFFFF'});
	ac.active = null;
}

autocomplete.prototype.abort = function () {
	var ac = this;
	// abort any pending requests
	for (var i = 0; i < ac.xhrlist.length; i++) ac.xhrlist[i].abort();
	ac.div.hide();
};

autocomplete.prototype.fetchresults = function (tbox) {
	var ac = this;
	// abort any pending requests
	for (var i = 0; i < ac.xhrlist.length; i++) ac.xhrlist[i].abort();
	
	// hide the box if we're under the character limit
	if (tbox.value.length < 3) { ac.div.hide(); return; }
	
	ac.tobereset = true;
	for (var j = 0; j < ac.sources.length; j++) {
		
		var q = ac.sources[j].q.replace(/CURRENTVAL/, escape(tbox.value));
		if (ac.xhrrelay) q = ac.xhrrelay.replace(/SOURCEURL/, escape(q));
		var h = ac.sources[j].h;
		var json = ac.sources[j].json;
		// need this variable so that we can clear the div once, then add values from multiple sources
		ac.xhrlist[j] = new Ajax.Request(q, {
			method: 'get',
			evalJSON: (json ? 'force' : false),
			onException: function (e) {
				// don't care
			},
			onSuccess: function (t) {
				var data = [];
				if (json) data = h(t.responseJSON);
				else data = h(t.responseText);
				if (t.aborted) return;
				ac.addresults(data, tbox);
			}
		});
	}
};

autocomplete.prototype.addresults = function(data, tbox) {
	var ac = this;
	var div = this.div;
	if (ac.tobereset) {
		ac.items = [];
		ac.resetactive();
		div.hide();
		div.innerHTML = '';
		var oset = tbox.cumulativeOffset();
		div.setStyle({
			left: oset[0]+'px',
			top: (oset[1]+tbox.getHeight()+2)+'px',
			minWidth: tbox.getWidth()+'px'
		});
		ac.tobereset = false;
	}
	for (var k = 0; k < data.length && ac.items.length < ac.maxrows; k++) {
		ac.listitem(data[k], tbox);
	}
	if (data.length) div.show();
};

autocomplete.prototype.listitem = function(item, tbox) {
	var ac = this;
	var value = item.value;
	var display = item.display;
	var id = item.id;
	if (!value) value = display;
	if (!display) display = value;
	var li = $(document.createElement('div'));
	li.setStyle({padding: '2px'});
	li.innerHTML = display;
	
	var curridx = this.items.length;
	li.observe('mouseover', function() {
		ac.makeactive(curridx);
	});
	li.observe('mousedown', function(e) {
		e.stop();
	});
	li.observe('click', function() {
		ac.dofill(item, tbox);
	});
	
	this.div.appendChild(li);
	this.items.push(item);
};

autocomplete.prototype.dofill = function (row, tbox) {
	this.abort();
	this.finalcallback(row, tbox);
};

/**
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 */
Ajax.Request.prototype.abort = function() {
  if (Ajax.activeRequestCount) Ajax.activeRequestCount--;
	// prevent any state change callbacks from being issued
	this.transport.onreadystatechange = Prototype.emptyFunction;
	// abort the XHR
	this.transport.abort();
	// set a flag, just in case
	this.transport.aborted = true;
};