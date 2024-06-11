define(["jquery"], function($) {
    return {
        move : function() {

            if (document.getElementById('fitem_id_customfield_app_background')) {
                if (document.getElementById('fitem_id_overviewfiles_filemanager')) {
                    var app_background = $("#fitem_id_customfield_app_background");
                    var filemanager = $("#fitem_id_overviewfiles_filemanager");

                    var categoryid = app_background.parent().parent().attr("id");

                    app_background.appendTo(filemanager.parent());

                    $("#" + categoryid).remove();
                }
            }
        }
    };
});
