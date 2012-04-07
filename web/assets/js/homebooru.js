//$('#tos').hide();
$(document).ready(function() {
	$("#touch").hover(
		function() {
			$("#poweredby").fadeIn(150)
		},
		function() {
			$("#poweredby").fadeOut(150)
		}
	);

	$('.js-relative-date').relatizeDateTime()
	setInterval("$('.js-relative-date').relatizeDateTime()", 60)

	// Disable pagination dummy links
    $('div.pagination li.disabled [href^=#], div.pagination li.active [href^=#], .tsun a.btn.disabled').on('click', function (e) {
		e.preventDefault()
    })

	// tos link
	//$('#toslink').on('click', function(e) {
	//	e.preventDefault()
	//	$('#tos').slideToggle('fast')
	//})

	// clickable header
	$('header .logo img, header h1').on('click', function(e) {
		e.preventDefault()
		window.location.href = $('header nav').attr('data-home-url')
	})
});
