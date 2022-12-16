jQuery(document).ready(function () {

    if (jQuery('.epaper-form form').length) {
        store_epaper_form_data();
    }

    jQuery('#epaperModalTab a').on('click', function (e) {
        e.preventDefault()
        jQuery(this).tab('show')
    })
});

// store formdata in session
function store_epaper_form_data() {
    jQuery('.epaper-form form').submit(function (e) {

        let form = jQuery(this);
        let date = jQuery(this).find('.dateSelect').find(":selected").val();
        let productID = jQuery(this).find('.productSelect').val();
        let cartEmpty = jQuery(this).find('.CartEmpty');
        let button = jQuery(this).find("button[type='submit']");
        let buttonText = jQuery(this).find("button[type='submit']").html();
        let loadingIcon = '<i class="fas fa-spinner"></i>';

        button.find('i').remove();

        if(cartEmpty.val() == 'false'){
            e.preventDefault();
            jQuery('#lawiEpaperCleanCartModal').modal();

            jQuery('.emptyCart').on('click', function (event){
                button.html( loadingIcon + ' ' + buttonText );
                button.find('i').addClass('fa-spin');
                cartEmpty.val('true');
                jQuery('#lawiEpaperCleanCartModal').modal('hide');

                button.click();
                form.submit();
            });

            return;
        }

        if(button.data('modal') == 'login'){
            e.preventDefault();
            jQuery('#lawiEpaperModal').modal();

            wp.ajax.post('addToCartExtraData', {
                date: date,
                id: productID
            }).done(response => {

            }).fail(response => {
                //console.log(response);
            })
        }else{
            button.html( loadingIcon + ' ' + buttonText );
            button.find('i').addClass('fa-spin');
        }
    });
}