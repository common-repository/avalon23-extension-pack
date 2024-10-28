function avalon23_change_preloader_image(button) {

    var image = wp.media({
        title: avalon23_helper_vars.lang.select_table_thumb,
        multiple: false,
        library: {
            type: ['image']
        }
    }).open()
            .on('select', function (e) {
                var uploaded_image = image.state().get('selection').first();
                uploaded_image = uploaded_image.toJSON();
                button.parentNode.querySelector('.avalon23_delete_img').style.display = "block";
                if (typeof uploaded_image.url != 'undefined') {
                    if (typeof uploaded_image.sizes.thumbnail !== 'undefined') {
                        button.querySelector('img').setAttribute('src', uploaded_image.sizes.thumbnail.url);
                    } else {
                        button.querySelector('img').setAttribute('src', uploaded_image.url);
                    }
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: avalon23_helper.prepare_ajax_form_data({
                            action: 'avalon23_save_settings_field',
			    field: 'preload_image',
                            value: uploaded_image.id
                        })
                    }).then(response => response.text()).then(data => {
                        

                        avalon23_helper.message(avalon23_helper_vars.lang.saved);
                    }).catch((err) => {
                        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
                    });

                }
            });


    return false;

}

function avalon23_delete_preloader_image(button){
    var src= button.getAttribute('data-src');
    fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: avalon23_helper.prepare_ajax_form_data({
	    action: 'avalon23_save_settings_field',
	    field: 'preload_image',
            value: 0
        })
    }).then(response => response.text()).then(data => {
        //avalon23_image_container
        button.parentNode.querySelector('img').setAttribute('src', src);
        button.style.display = "none";
        avalon23_helper.message(avalon23_helper_vars.lang.saved);
    }).catch((err) => {
        avalon23_helper.message(avalon23_helper_vars.lang.error + ' ' + err, 'error');
    });
}
document.addEventListener('avalon23-draw-settings-table', (e) => {
    let _this = e.detail.settings_table;
    _this.table.querySelectorAll('.avalon23-color-field').forEach(item => {
	jQuery(item).spectrum({
	    type: 'text',
	    allowEmpty: true,
	    showInput: true,
	    change: function (color) {
		item.setAttribute('value', color.toHexString());
		_this.save(0, "preload_color", item.value);
	    }
	});
    });    
});
//avalon23-draw-settings-table
//	this.table.querySelectorAll('.avalon23-color-field').forEach(item => {
//            jQuery(item).spectrum({
//                type: 'text',
//                allowEmpty: true,
//                showInput: true,
//                change: function (color) {
//                    item.setAttribute('value', color.toHexString());
//                    item.dispatchEvent(new Event('change'));
//                }
//            });
//        });
//avalon23-draw-settings-table

