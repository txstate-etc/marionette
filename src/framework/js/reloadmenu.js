function reloadmenu(depth, myform) {
	var to = names[depth];
	var from = names[depth-1];
	eval("var reloadbox = myform."+to+";");
	var start = 0;
	for (var i = fdepth[depth]; i < depth; i++) {
		eval("var mybox = myform."+names[i]+";");
		if (mybox.selectedIndex == 0) break;
		var slctd = mybox.selectedIndex-1;
		var oldstart = start;
		eval("var start = start"+names[i+1]+"["+(oldstart+slctd)+"];");
	}
	if (mybox.selectedIndex != 0) eval("var end = end"+names[depth]+"["+(oldstart+slctd)+"];");
	
	reloadbox.innerHTML = '';
	reloadbox.options[0] = new Option('------------', '');
	if (mybox.selectedIndex != 0) {
		for (var i = start; i < end; i++) {
			eval("var valu = "+names[depth]+"[i];");
			var temp = new Array();
			temp = valu.split('%|%');
			var va = '';
			var na = '';
			if (temp.length == 2) {
				va = temp[0];
				na = temp[1];
			} else {
				va = valu;
				na = valu;
			}
			if (va != '') reloadbox.options[i-start+1] = new Option(na, va);
		}
	}
	for (var i = depth + 1; fdepth[i] == fdepth[depth]; i++) {
		eval("var reloadbox = myform."+names[i]+";");
		reloadbox.innerHTML = '';
		reloadbox.options[0] = new Option('------------', '');
	}
}