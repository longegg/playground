(function($) {

    var cookieName = "access_token";
    var $cookieInfoBox = $("#cookieInfo");

    $("#link").click(function(){ 
        runAjax(); 
    });

    $("#cookieCreate").click(function(){
        var cookieValue = "Cookie-" + Date.now();
        Cookies.set(cookieName, cookieValue);
    });

    $("#cookieRead").click(function(){
        var cookieValue = Cookies.get(cookieName);
        var newCookieValue = cookieValue == undefined ? "No cookie" : cookieValue;
        $cookieInfoBox.html(newCookieValue);
    });  
    
    $("#cookieDelete").click(function(){ 
        Cookies.remove(cookieName);
    });      

    function runAjax() {
        $.ajax({
            url: "proxy.php",
            type: "GET",
            data: {
                salonId: 1,
                method: "GetStuff"
            },            
            success: function(result){
                console.log(result);
            }
        });
    }

})(jQuery);