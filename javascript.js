var NDY = YUI().use("node", function(Y) {
    var choicegroup_memberdisplay_click = function(e) {

        var names = Y.all('div.choicegroups-membersnames'),
            btnShowHide = Y.all('a.choicegroup-memberdisplay');

        btnShowHide.toggleClass('hidden');
        names.toggleClass('hidden');

        e.preventDefault();

    };
    Y.on("click", choicegroup_memberdisplay_click, "a.choicegroup-memberdisplay");
});