
function store-epaper-form-data() {

}
$(document).ready(function() {


    alert('loaded');
    $('#epaper-id-14').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: 'epaper-session.php',
            data: $(this).serialize(),
            success: function(response)
            {
                var jsonData = JSON.parse(response);

                // user is logged in successfully in the back-end
                // let's redirect
                if (jsonData.success == "1")
                {
                    location.href = 'my_profile.php';
                }
                else
                {
                    alert('Invalid Credentials!');
                }
            }
        });
    });
});
