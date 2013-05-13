/**
 * @namespace
 */
M.mod_choicegroup = M.mod_choicegroup || {};

/**
 * This function is initialized from PHP
 *
 * @param {Object} Y YUI instance
 */
M.mod_choicegroup.init = function(Y) {
  this.Y = Y;
	// The button on which the fill-in begins
	var button = Y.one("#id_setlimit");
	var textfield = Y.one("#id_generallimitation");
	
	//On click fill in the limit in every field
	button.on('click', function (e) {
		//Get the new string
		var text = Y.one("#id_generallimitation").get('value');
		//Build a selector get the elements 
		var selector = 'input[value="0"]';
		var limits = Y.all(selector);
		//Set the new value
		limits.set('value',text);
	});
	
}


