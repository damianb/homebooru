//$('#tos').hide();
$(document).ready(function() {
	$("#touch").hover(
		function() { $("#poweredby").fadeIn(150) },
		function() { $("#poweredby").fadeOut(150) }
	);

	$('.js-relative-date').relatizeDateTime()
	setInterval("$('.js-relative-date').relatizeDateTime()", 60)

	// Disable pagination dummy links
    $('div.pagination li.disabled [href^=#], div.pagination li.active [href^=#], .editpost .rating-group button').on('click', function (e) {
		e.preventDefault()
    })
	$('form#editpost').on('submit', function() {
		var input = $('<input>').attr('type', 'hidden').attr('name', 'rating');
		if($('#rating-safe').hasClass('active')) {
			$(input).val('safe');
		}
		else if($('#rating-questionable').hasClass('active')) {
			$(input).val('questionable');
		}
		else if($('#rating-explicit').hasClass('active')) {
			$(input).val('explicit');
		}

		if($(input).val() != '') {
			$(this).append($(input))
		}
	})

	// clickable header
	$('.fullhome h1').on('click', function(e) {
		e.preventDefault()
		window.location.href = $('nav').attr('data-home-url')
	})
});
