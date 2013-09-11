(function($) {

	$(document).ready(function() {

		$('.aws-settings').each(function() {
			var $container = $(this);

			$('.reveal-form a', $container).click(function() {
				$('form', $container).show();
				return false;
			});
		});

	});

})(jQuery);