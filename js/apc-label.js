(function($){
    $('body').on("click", ".print_label", function(e) {
        $('.wpcf7-spinner').css({ "visibility": "visible" });
        var order_number = $(this).attr('order_number');
        $.ajax({
            type: "POST",
            url: apc_label_object.ajax_url,
            data: { action: "print_label", order_number: order_number },
            success: function(response) {
                var a = document.createElement("a"); //Create <a>
                a.href = "data:image/png;base64," + response; //Image Base64 Goes here
                a.download = "label.png"; //File name Here
                $('.wpcf7-spinner').css({ "visibility": "hidden" });
                a.click(); //Downloaded file
            }
        });

    });
})(jQuery);