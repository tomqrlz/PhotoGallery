<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
define("IN_INDEX", 1);
// require __DIR__ . '/vendor/autoload.php';

// Podłączamy konfigurację bazy danych i połączenie z PDO
include("config.inc.php");
if (isset($config) && is_array($config)) {
    try {
        $dbh = new PDO('mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4', $config['db_user'], $config['db_password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        print "Nie mozna polaczyc sie z baza danych: " . $e->getMessage();
        exit();
    }
} else {
    exit("Nie znaleziono konfiguracji bazy danych.");
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link rel="shortcut icon" type="image/x-icon" href="star.ico" />

	<title>Photo Gallery &trade;</title>

  <meta name="description" content="Photo Gallery by TM" />
  <meta name="keywords" content="gallery, pics, pictures, picture, art, photo, photography, exhibition" />
  
  <!-- bootstrap -->        
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />

  <!-- drag&drop -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script> 
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

  <!-- fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">

  <!-- css sheet -->
  <link rel="stylesheet" href="/style.css" type="text/css" />
  <link rel="stylesheet" href="/css/fontello.css" type="text/css" />

</head>

<body onload="countdown();">
  <!-- Górny pasek nawigacyjny -->
	<nav class="navbar navbar-expand-sm navbar-light bg-light fixed-top">
  <div class="container">
    <a class="navbar-brand" href="/">Photo Gallery &trade;</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item active">
          <a class="nav-link" href="/">home</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            categories
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
<?php
          // Pobieranie nazw kategorii do wypisania w liście rozwijanej
          $stmt = $dbh->prepare("SELECT title FROM categories");
          $stmt ->execute();

          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
            $titleModified = strtolower(str_replace(" ", "-", $title));
            print '<a class="dropdown-item" href="/category/'. $titleModified .'">'. $title .'</a>';
          };   
?>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="/#div-new-cat">Add a category</a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/add_photo">add a photo</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/instructions">instructions</a>
        </li>
      </ul>
      <ul class="navbar-nav navbar-right">
        <li class="nav-item">
          <span class="navbar-text" id="clock"></span>
        </li>
      </ul>
    </div>
  </div>
  </nav>

  <div class="container">
    <?php
    
    // Tablica z dozwolonymi podstronami
    $allowed_pages = ['main', 'category', 'add_photo', 'instructions'];

    // Strona wczytuje daną podstronę w zależności jakie wysyłamy żądanie
    if (isset($_GET['page']) && $_GET['page'] && in_array($_GET['page'], $allowed_pages)) {
      if (file_exists($_GET['page'] . '.php')) {
        include($_GET['page'] . '.php');
      } else {
        print 'Plik ' . $_GET['page'] . '.php nie istnieje.';
      }
    }
    else {
      include('main.php');
    }
    ?>

  </div>

  <!-- Stopka -->
  <footer class="footer mt-auto bg-light">
    <div class="container">
      <span class="text-dark">2020 TI AGH Created By TM</span>
    </div>
  </footer>
</body>
</html>

<!-- Skrypt na zegarek -->
<script type="text/javascript">
    function countdown()
      {
        var dzisiaj = new Date();
        
        var godzina = dzisiaj.getHours();
        if (godzina<10) godzina = "0"+godzina;
        var minuta = dzisiaj.getMinutes();
        if (minuta<10) minuta = "0"+minuta;
        var sekunda = dzisiaj.getSeconds();
        if (sekunda<10) sekunda = "0"+sekunda;
        
        document.getElementById("clock").innerHTML = godzina+":"+minuta+":"+sekunda;
        setTimeout("countdown()",1000);
      }
      
</script>