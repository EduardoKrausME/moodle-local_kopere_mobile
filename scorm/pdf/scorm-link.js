function initModalPdf() {

    if ($("#modal-pdf").length)
        return;

    var modalHtml =
            "<div class='modal-pdf' id='modal-pdf'>" +
            "    <div class='modal-internal'>" +
            "        <iframe id='iframe-modal-pdf' style='width:100%;height:100%;'" +
            "                frameborder='0' allowfullscreen" +
            "                sandbox='allow-scripts allow-same-origin allow-popups'" +
            "                allow=':encrypted-media; :picture-in-picture'></iframe>" +
            "    </div>" +
            "</div>";

    $("body").append(modalHtml);
}

setInterval(function() {
    var $a = $("a:not(.kraus-processado)");
    if ($a.length) {

        $a.each(function(id, element) {

            var link = $(element);
            link.addClass("kraus-processado");

            console.log(link);

            var href = link.prop("href");

            if (href.indexOf(".pdf") > 1) {

                link.click(function() {
                    event.stopImmediatePropagation();
                    event.stopPropagation();
                    event.preventDefault();

                    $('#modal-pdf').modal({
                        fadeDuration : 100,
                        fadeDelay    : 1.75,
                    });

                    var fullurl = "/mod/scorm/pdf/pdfjs-2.8.335-legacy/web/viewer.html?file=" + encodeURI(href);
                    $("#iframe-modal-pdf").attr("src", fullurl);
                });

                initModalPdf();
            }
            else {
                link.click(function() {
                    event.stopImmediatePropagation();
                    event.stopPropagation();
                    event.preventDefault();

                    if (href.indexOf(location.host) > 1) {
                        console.log(href);
                    } else {
                        console.log(href);
                        window.top.postMessage({
                            source : "scorm",
                            href   : href
                        }, "*");
                    }
                });
            }
        });
    }
}, 1000);

window.open = function(url, target, features, replace) {
    window.top.postMessage({
        source : "scorm",
        href   : url
    }, "*");
}