jQuery(document).ready(function (){
    if(jQuery('.epaper-form form').length){
        store_epaper_form_data();
    }
});

// store formdata in session
function store_epaper_form_data() {
    jQuery('.epaper-form form').submit(function (e) {

        let date = jQuery(this).find('.dateSelect').find(":selected").val();
        let productID = jQuery(this).find('.productSelect').val();
        let button = jQuery(this).find("input[type='submit']");

        if(button.data('modal') == true){
            e.preventDefault();
            jQuery('#lawiEpaperModal').modal();

             wp.ajax.post( 'addToCartExtraData', {
                 date : date,
                 id: productID
             } ).done( response => {
                 //let data = JSON.parse(response);
                 //consol.log(data);
                 //jQuery(this).submit();
             } ).fail( response => {
                 //console.log(response);
             })
        }
    });
}
