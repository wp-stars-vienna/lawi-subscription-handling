jQuery(document).ready(function (){
    if(jQuery('.epaper-form form').length){
        store_epaper_form_data();
    }
});

// store formdata in session
function store_epaper_form_data() {
    jQuery('.epaper-form form').submit(function (e) {

        let form = jQuery(this);
        let date = jQuery(this).find('.dateSelect').find(":selected").val();
        let productID = jQuery(this).find('.productSelect').val();
        let cartEmpty = jQuery(this).find('.CartEmpty');
        let button = jQuery(this).find("input[type='submit']");

        if(cartEmpty.val() == 'false'){
            e.preventDefault();
            jQuery('#lawiEpaperCleanCartModal').modal();

            jQuery('.emptyCart').on('click', function (event){
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

             wp.ajax.post( 'addToCartExtraData', {
                 date : date,
                 id: productID
             } ).done( response => {

             } ).fail( response => {
                 //console.log(response);
             })
        }
    });
}
