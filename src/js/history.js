Event.observe(document, 'dom:loaded', function (event) {
	$$('.attach_expand').each(function (itm) {
		var adiv = itm.next();
		adiv.setStyle({display: 'block'});
		if (adiv.getHeight() < 15) {
			itm.setStyle({display: 'none'});
		} else {
			adiv.setStyle({display: 'none'});
			itm.observe('click', function (e) {
				itm.setStyle({display: 'none'});
				adiv.setStyle({display: 'block'});
			});
		}
	});
});