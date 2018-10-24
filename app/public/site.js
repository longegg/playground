(function($) {

    var cookieName = "access_token";
    var $cookieInfoBox = $("#cookieInfo");

    $("#link").click(function(){ 
        runAjax(); 
    });

    $("#proxy").click(function(){ 
        runProxy(); 
    }); 
    
    $("#isAuth").click(function(){ 
        getSession(); 
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
                salonId: 26,
                method: "GetStuff"
            },            
            success: function(result){
                console.log(result);
            }
        });
    }

    function runProxy() {

        var data = {
            Field: "Email",
            Value: "oyverik@gmail.com",
            Password: "2sCd"    
        };

        $.ajax({
            url: "proxy.php?method=customer%2Fsearch&salonId=1",
            type: "POST",
            data: JSON.stringify(data),
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            success: function(result){
                console.log(result);
            }
        });
    } 
    
    function getSession() {
        $.ajax({
            url: "proxy.php?method=customer%2FisAuthenticated&salonId=1",
            type: "GET",
            success: function(result){
                console.log(result);
            }
        });
    }     

})(jQuery);