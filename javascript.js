var NDY = YUI().use("node", function(Y) {
    var choicegroup_memberdisplay_click = function(e) {

        var names = Y.all('div.choicegroups-membersnames'),
            theBtn = Y.one('div.choicegroup-memberdisplay');
        if (theBtn.get('innerHTML') == '+') {
            theBtn.set('innerHTML', '-');
        }
        else {
            theBtn.set('innerHTML', '+');
        }
        names.toggleClass('hidden');

    };
    Y.on("click", choicegroup_memberdisplay_click, ".choicegroup-memberdisplay");
});