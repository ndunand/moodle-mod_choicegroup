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
    var the_button = Y.one("#id_setlimit"),
        the_items = Y.all('input.mod-choicegroup-limit-input');

    // On click fill in the limit in every field
    the_button.on('click', function (e) {
        // Get the value string
        var text_value = Y.one("#id_generallimitation").get('value');
        // Make sure we've got an integer value
        var int_value = parseInt(text_value);
        if (!isNaN(int_value)) {
            // Set all new values
            the_items.set('value', int_value);
        }
    });

}


