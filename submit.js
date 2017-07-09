(function(){
	function submit(e) {
		e.preventDefault();
		var form = this;
		if ( ! form.checkValidity() ) {// stupid custom validation for safari
			form.insertAdjacentHTML('beforeend','<style>#mnmlcontact :invalid{border-color:#f66;}</style>');
		} else {
			form.querySelector('input[type=submit]').style.visibility = "hidden";
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/wp-json/mnmlcontact/v1/submit');
			// xhr.setRequestHeader( 'X-WP-Nonce', POST_SUBMITTER.nonce );
			xhr.onload = function() {
				form.innerHTML = '<p class="mnmlcontact-success">Thanks!</p>';
				// form.className += ' mnmlcontact-success';
			};
			xhr.onerror = function() {
				console.log(this.responseText);
			};
			xhr.send(new FormData(form));
			// xhr = null;
		}
	}
	document.getElementById('mnmlcontact').addEventListener('submit', submit );
})();

