jQuery(document).ready(function() {

	var data = {
		action: 'purge_all',
	};

	jQuery('#purge-varnish').on('click', function(event){
		event.preventDefault();
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#varnish-purge > .inside').html(response.msg);
		}, 'json');
	});
});