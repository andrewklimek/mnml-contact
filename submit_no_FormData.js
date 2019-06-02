(function(){
	// handle form serialization as an alternative to using FormData objects (if you need IE 9). add 
	function serialize(obj) {
		// if(obj.nodeName == "FORM"){
		var field, s = [];
		var len = obj.elements.length;
		for (i=0; i<len; i++) {
			field = obj.elements[i];
			if ( ! (field.type == 'submit' || field.type == 'button' || field.disabled || field.type == 'file' || field.type == 'reset') && field.name ) {
				if (field.type == 'select-multiple') {
					for (j=obj.elements[i].options.length-1; j>=0; j--) {
						if(field.options[j].selected)
							s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
					}
				} else if ( ! (field.type == 'checkbox' || field.type == 'radio') || field.checked) {
					s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
				}
			}
		}
		return s.join('&').replace(/%20/g, '+');
		// }
		// else if ( typeof obj == 'object' ) {
			// return Object.keys(data).map( function(k){ return k + '=' + encodeURIComponent(data[k]) } ).join('&');
		// }
	}
	
	function submit(e) {
		e.preventDefault();
		var form = this;
		if ( ! form.checkValidity() ) {// stupid custom validation for safari
			form.insertAdjacentHTML('beforeend','<style>#mnmlcontact :invalid{border-color:#f66;}</style>');
		} else {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/wp-json/mnmlcontact/v1/s');
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
			// xhr.setRequestHeader( 'X-WP-Nonce', POST_SUBMITTER.nonce );
			xhr.onreadystatechange = function() {
				if (this.readyState === 4) {
					if (this.status >= 200 && this.status < 400) {
						console.log(this.responseText);
						form.innerHTML = '<p class="mnmlcontact-success">Merci!</p>';
						// form.className += ' mnmlcontact-success';
					} else {
						console.log(this.responseText);
					}
				}
			};
			xhr.send(serialize(form));
			// xhr = null;// IE8??
		}
	}
	document.getElementById('mnmlcontact').addEventListener('submit', submit );
})();