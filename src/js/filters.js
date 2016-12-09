function create_select(parent, name, data) {
	var slct = document.createElement('select');
	slct.name = name;
	slct.id = name;
	
	data.each( function (o) {
		var opt = document.createElement('option');
		opt.value = o[0];
		opt.innerHTML = o[1];
		slct.appendChild(opt);
	});
	
	parent.appendChild(slct);
}

function create_datebox(parent, name, def) {
	var num = name.replace(/\D+/, '');
	if (!def) def = today();

	var ipt = document.createElement('input');
	ipt.type = 'text';
	ipt.name = name;
	ipt.size = 10;
	ipt.value = def;
	ipt.id = name;
	
	add_text(parent, ' ');
	
	var lnk = document.createElement('a');
	lnk.href = '#';
	lnk.id = 'button'+num;
	
	var img = document.createElement('img');
	img.src = phpmanage_cal_path;
	img.width = 16;
	img.height = 16;
	img.className = 'calimg';
	
	parent.appendChild(ipt);
	parent.appendChild(lnk);
	lnk.appendChild(img);
	
	Calendar.setup({inputField : name, ifFormat : '%m/%d/%Y', button : 'button'+num});
}

function create_tbox(parent, name, def, size) {		
	var ipt = document.createElement('input');
  ipt.type = 'text';
  ipt.name = name;
  ipt.size = size;
  ipt.value = def;
  ipt.id = name;
	parent.appendChild(ipt);
}

function add_text(parent, text) {
	// stupid complicated way to add a space to the end of a div
	var txt = document.createTextNode(text);
	parent.appendChild(txt);
}

function today() {
	var now = new Date();
	return now.format('mm/dd/yyyy');
}

function create_extra_buttons(fld) {
	var opt = fld.options[fld.selectedIndex];
	var num = fld.name.replace(/\D+/, '');
	var par = fld.up();

	// destroy existing control and value fields
	par.removeChild(fld);
	par.innerHTML = '';
	par.appendChild(fld);

	add_text(par, ' ');

	if (opt.hasClassName('list')) {
	  create_select(par, 'control'+num, [
			['equal', 'may be'],
			['notequal', 'may not be']
		]);
		add_text(par, ' ');
		create_select(par, 'val'+num, phpmanage_list_data[opt.value]);
	} else if (opt.hasClassName('date')) {
	  create_select(par, 'control'+num, [
	    ['gt', '>='],
	    ['lt', '<=']
	  ]);
		add_text(par, ' ');
	  create_datebox(par, 'val'+num);
	} else if (opt.hasClassName('char') || opt.hasClassName('pri')) {
	  create_select(par, 'control'+num, [
	    ['gt', '>='],
	    ['lt', '<=']
	  ]);
		add_text(par, ' ');
	  create_tbox(par, 'val'+num, '', 2);
	} else if (opt.hasClassName('search')) {
		create_select(par, 'control'+num, [
			['maycont', 'may contain'],
			['mustcont', 'must contain'],
			['notcont', 'must not contain']
		]);
		add_text(par, ' ');
		create_tbox(par, 'val'+num, '', 20);
	}
	
	// create a link that can remove this filter
	add_text(par, ' ');		// give it some space
	// create the link
	var lnk = document.createElement('a');
	lnk.id = 'rem'+num;
	lnk.href = '#';
	lnk.innerHTML = 'remove';
	par.appendChild(lnk);
	
	// give the remove link something to do
	if (num == 1) {
		// first remove link won't actually delete
		$(lnk).observe('click', function (event) {
			this.blur();
			fld.selectedIndex = 0;
			create_extra_buttons(fld);
			event.stop();
		});
	} else {
		$(lnk).observe('click', function (event) {
			this.blur();
			var mypar = this.up('.singlefilter');
			if (this.id == 'rem'+(create_new_filter.currnum-1)) create_new_filter.currnum--;
			mypar.up().removeChild(mypar);
			event.stop();
		});		
	}
}

function create_new_filter(par) {
	if (!create_new_filter.currnum) create_new_filter.currnum = 2;
	var template = $('field1');
	var num = create_new_filter.currnum++;
	
	// create the div to contain the new filter
	var div = document.createElement('div');
	div.className = 'singlefilter';
	par.appendChild(div);
	
	// create the first select box for the filter
	var newfilt = document.createElement('select');
	newfilt.id = 'field'+num;
	newfilt.name = 'field'+num;
	newfilt.className = 'field';
	newfilt.innerHTML = template.innerHTML;
	newfilt.selectedIndex = 0;
	
	// put the select box in the document
	div.appendChild(newfilt);
	
	// initialize
	create_extra_buttons(newfilt);
	
	// observe changes
	$(newfilt).observe('change', function (event) {
		create_extra_buttons(this);
	});
	
	return $(newfilt);
}

function select_setval(slct, val) {
	for (var i = 0; i < slct.options.length; i++) {
		var opt = slct.options[i];
		if (opt.value == val) slct.selectedIndex = i;
	}
}

var filters_curr = 1;

Event.observe(document, 'dom:loaded', function (event) {

	// initialize and preload if we have saved filters
	if (typeof(phpmanage_preloads) != "undefined") {
		for (var i = 0; i < phpmanage_preloads.length; i++) {
			var fld = $('field'+(i+1));
			var fldval = phpmanage_preloads[i].field;
			
			if (i > 0) fld = create_new_filter($('filtersonly'));
			
			select_setval(fld, fldval);
			create_extra_buttons(fld);
			
			var cntl = $('control'+(i+1));
			var cntlval = phpmanage_preloads[i].control;
			select_setval(cntl, cntlval);
			
			var valu = $('val'+(i+1));
			var valuval = phpmanage_preloads[i].val;
			if (valu.tagName == 'select') select_setval(valu, valuval);
			else valu.value = valuval;
		}
	} else {
		$('field1').selectedIndex = 0;
	}

	// toggle the area for editing filters
	$('filtertoggle').down('a').observe('click', function (event) {
		this.blur();
		var fa = $('filterarea');
		var tog = (fa.getStyle('display') == 'block' ? 'none' : 'block');
		fa.setStyle({display: tog});
		if (tog == 'block') {
			this.addClassName('open');
		} else {
			this.removeClassName('open');
		}
		event.stop();
	});
	
	// watch for a change on any of the filter lines
	$('field1').observe('change', function (event) {
	  create_extra_buttons(this);
	});

	$('addfilterbutton').observe('click', function (event) {
		this.blur();
		create_new_filter($('filtersonly'));
		event.stop();
	});
});