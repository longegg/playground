<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>The HTML5 Herald</title>
  <meta name="description" content="The HTML5 Herald">
  <meta name="author" content="SitePoint">

  <link rel="stylesheet" href="css/styles.css?v=1.0">
</head>

<body>
    <h1>PHP Playground</h1>

    <h2>Ajax</h2>
    <p>
        <a id="link" href="#">Run Ajax</a>
    </p>

    <h2>Proxy v2</h2>
    <p>
        <a id="proxy" href="#">Log in</a>
        <a href="#" id="isAuth">Get Session</a>
    </p>

    <h2>Cookies</h2>
    <p>
        <a id="cookieCreate" href="#">Create cookie</a> |
        <a id="cookieRead" href="#">Read cookie</a> |
        <a id="cookieDelete" href="#">Delete cookie</a>    
    </p>
    <textarea name="" id="cookieInfo" cols="30" rows="10"></textarea>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js"></script>
  <script src="site.js"></script>
</body>
</html>