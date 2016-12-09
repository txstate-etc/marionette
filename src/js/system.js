Event.observe(document, 'dom:loaded', function (event) {

	// add draggables and droppables for all the areas, and make them switch to edit mode
	// on a double-click
	$$('.area_item').each( function (item) {
		// Draggable definition for the item
		new Draggable(item, { revert: true });
				
		// Droppable definition
		Droppables.add(item, {
			accept: "area_item",
			onDrop: movearea,
			hoverclass: "area_item_hilite"
		});
		
		// make the area editable
		item.observe('dblclick', function (event) {
			createareabox(this, this.parentNode, this.id, this.down('.area_text').innerHTML);		
		});
	});
	
	// add droppables for the lines between the areas, for re-ordering
	$$('.above').each( function (item) {
		Droppables.add(item, {
			accept: "area_item",
			hoverclass: "above_hilite",
			onDrop: movearea
		});
	});

	// add a droppable for the trash can
	Droppables.add("areatrash", {
		accept: "area_item",
		hoverclass: "trash_hilite",
		onDrop: movearea
	});
});

function tempremove(drag) {
	var myli = drag.parentNode;
	myli.parentNode.removeChild(myli);
}

var movearea = function(drag, drop) {
	tempremove(drag);
	req = new XMLHttpRequest();
	req.open("GET", "ajax.php?action=movearea&drag="+drag.id+"&drop="+drop.id);
	req.onreadystatechange = function (evt) {
		if (req.readyState == 4) {
			window.location = "system.php";
		}
	}
	req.send(null);
}

function select_setlabel(slct, label) {
	for (var i = 0; i < slct.options.length; i++) {
		var opt = slct.options[i];
		if (opt.innerHTML == label) { slct.selectedIndex = i; return; }
	}
}

function createareabox(clicked, parent, name, value) {
	// hide the moveable span
	clicked.style.display="none";
	
	// get an element to insert before (likely a ul)
	before = parent.childNodes[0];
	
	// create a text box to be used to update a value
	tbox = document.createElement("input");
	tbox.name = name;
	tbox.type = "text";
	tbox.size = 45;
	tbox.value = value.unescapeHTML();
	tbox.style.fontSize = "10px";
	parent.insertBefore(tbox, before);
	
	// create a drop box to allow them to select a program manager for the area
	sbox = document.createElement("select");
	sbox.name = "man"+name;
	sbox.className = "areamanslct";
	sbox.innerHTML = $('manareanew').innerHTML;
	var preload = $(clicked).down('.area_manager');
	if (preload) preload = preload.down('span');
	if (preload) select_setlabel(sbox, preload.innerHTML);
	parent.insertBefore(sbox, before);
	
	// create a save button
	btn = document.createElement("input");
	btn.name = "pwo_submit";
	btn.type = "image";
	btn.value = "Submit";
	btn.height = 11;
	btn.width = 10;
	btn.src = phpmanage_img_root+'/left.gif';
	btn.className = "area_save_button";
	parent.insertBefore(btn, before);
		
	// undo the last one we created, so user cannot open a bunch at once
	if (typeof createareabox.areabox != "undefined") {
		createareabox.hiddenrow.style.display="block";
		createareabox.areabox.parentNode.removeChild(createareabox.areabox);
		createareabox.progman.parentNode.removeChild(createareabox.progman);
		createareabox.savebutton.parentNode.removeChild(createareabox.savebutton);
	}
	createareabox.areabox = tbox;
	createareabox.progman = sbox;
	createareabox.savebutton = btn;
	createareabox.hiddenrow = clicked;
}