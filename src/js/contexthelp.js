function contexthelp(id, text, bbcode) {
	this.id = id;
	this.text = text;
	this.evaluated = (bbcode ? bbcode : text);
	document.observe('dom:loaded', this.init.bind(this));
}

contexthelp.prototype.init = function() {
	var ch = this;
	var lnk = $(this.id);
	var txt = this.text;

	// create our tooltip div, re-use it when we have multiple help items
	if (contexthelp.initiated) { 
		ch.div = contexthelp.div;
	} else {
		ch.div = $(document.createElement('div'));
		ch.div.id = 'contexthelptooltip';
		ch.div.setStyle({position: 'absolute', border: '1px solid #501214', backgroundColor: '#FFFFFF', padding: '5px', minWidth: '175px', maxWidth: '300px' });
		ch.div.hide();
		document.body.appendChild(ch.div);
		contexthelp.div = ch.div;
		contexthelp.initiated = true;
		ch.div.observe('mouseover', function(e) {
			clearTimeout(ch.div.timer);
		});
		ch.div.observe('mouseout', function(e) {
			if (!ch.div.neverHide) ch.div.timer = setTimeout(ch.div.hide.bind(ch.div), 250);
		});
		
		contexthelp.help = $(document.createElement('div'));
		ch.div.appendChild(contexthelp.help);
		
		if (contexthelp.editprivs) {
			var edit = $(document.createElement('a'));
			edit.innerHTML = 'Edit Help Text';
			edit.href = '#';
			edit.setStyle({display: 'block', textAlign: 'right', fontSize: '10px', marginTop: '3px'});
			edit.observe('click', function(e) {
				var curr = ch.div.currentCH.text;
				contexthelp.help.innerHTML = '';
				
				edit.setStyle({display: 'none'});
				ch.div.neverHide = true;
				
				var form = $(document.createElement('form'));
				form.writeAttribute('action', '#');
				
				var tarea = $(document.createElement('textarea'));
				tarea.rows = 5;
				tarea.cols = 30;
				tarea.innerHTML = curr;
				tarea.setStyle({display: 'block'});
				
				var help = $(document.createElement('a'));
				help.href = "#";
				help.innerHTML = "Style Tips";
				help.setStyle({position: 'absolute', left: '5px', bottom: '5px'});
				help.observe('click', function() {
					var helpwin = window.open(contexthelp.bbcodelink, 'bbcodehelp', 'width=600,height=768,resizable=yes,scrollbars=yes,toolbar=no,location=no,directories=no,statusbar=no,menubar=no,copyhistory=no'); 
					helpwin.focus();
				});
				
				var subm = $(document.createElement('input'));
				subm.type = "submit";
				subm.value = 'Save';
				subm.setStyle({float: 'right'});
				
				var canc = $(document.createElement('input'));
				canc.type = "submit";
				canc.value = 'Cancel';
				canc.setStyle({float: 'right'});
				
				form.appendChild(tarea);
				form.appendChild(help);
				form.appendChild(subm);
				form.appendChild(canc);
				contexthelp.help.appendChild(form);
				tarea.focus();
								
				var failfunc = function (t) {
					contexthelp.help.innerHTML = ch.div.currentCH.evaluated;
					ch.div.neverHide = false;
					edit.setStyle({display: 'block'});
					ch.div.timer = setTimeout(ch.div.hide.bind(ch.div), 1000);
				};

				canc.observe('click', function(e) {
					failfunc();
					e.stop();
				});

				form.observe('submit', function (e) {
					new Ajax.Request('xhr.php', {
						method: 'post',
						evalJSON: 'force',
						parameters: {id: ch.div.currentID, text: tarea.value, xhr: 'contexthelp'},
						onSuccess: function (t) {
							if (t.responseJSON.success) {
								var evald = (t.responseJSON.bbcode ? t.responseJSON.bbcode : tarea.value);
								contexthelp.help.innerHTML = evald;
								ch.div.currentCH.text = tarea.value;
								ch.div.currentCH.evaluated = evald;
								ch.div.neverHide = false;
								edit.setStyle({display: 'block'});
								ch.div.timer = setTimeout(ch.div.hide.bind(ch.div), 2000);
							} else failfunc(t);
						},
						onException: failfunc,
						onFailure: failfunc
					});
					e.stop();
				});
			});
			ch.div.appendChild(edit);
		}
	}
	
	lnk.observe('mouseover', function(e) {
		if (ch.div.neverHide) return;
		clearTimeout(ch.div.timer);
		contexthelp.help.innerHTML = ch.evaluated;
		ch.div.show();
		var h = lnk.getHeight();
		var newh = h;
		lnk.descendants().each(function(itm) {
			var myh = itm.getHeight();
			if (myh > newh) newh = myh;
		});
		var oset = lnk.cumulativeOffset();
		if (oset[0] > 650) {
			oset[0] -= ch.div.getWidth()+lnk.getWidth()+4;
		}
		ch.div.setStyle({
			left: (oset[0]+lnk.getWidth()+2)+'px',
			top: (oset[1]+h-newh)+'px'
		});
		ch.div.currentID = lnk.id;
		ch.div.currentCH = ch;
	});
	lnk.observe('mouseout', function(e) {
		if (!ch.div.neverHide) ch.div.timer = setTimeout(ch.div.hide.bind(ch.div), 250);
	});
}