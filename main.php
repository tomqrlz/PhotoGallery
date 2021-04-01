<?php
if (!defined('IN_INDEX')) { exit("Nie można uruchomić tego pliku bezpośrednio."); }
?>

<?php
  // Wyświetlanie kart ze zdjeciami danej kategorii (dane pobierane z bazy) 
  $stmt = $dbh->prepare("SELECT * FROM categories");
  $stmt ->execute();

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $title = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
    $description = htmlspecialchars($row['description'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
    $coverFullName = htmlspecialchars($row['coverFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

    $titleModified = strtolower(str_replace(" ", "-", $title));

    // Zabezpieczenie przed pobieraniem poprzez ustawienie zdjęcia jako tło diva oraz nałożenie przeźroczystej powłoki
    print '<div class="row">
            <a href="category/'. $titleModified .'" class="cover-photo-link">
              <div class="cover-photo" style="background-image: url(covers/'. $coverFullName .');">
                <div class="cover-text-overlay">
                  <h2>'. $title .'</h2>
                  <p>'. $description .'</p>
                </div> 
              </div>
            </a>
          </div>';
  }
?>


<br/><br/>
<div class="row">
  <!-- Tworzenie nowych kategorii -->
  <div id="div-new-cat">
    <p style="font-size: 28px;">create new photos category</p>
    <i class="icon-down-open"></i>
  </div>
  <br/>

  <div id="div-cat-form">

<?php
    if(isset($_POST['submit'])){
      $title = $_POST['title'];

      // Ustalanie nazwy zdjecia (okładki) kategorii do zapisu w folderze
      if(empty($title)){
        $titleModified = "unnamedCategory";
      } else {
        $titleModified = strtolower(str_replace(" ", "-", $title));
      }

      $description = $_POST['description'];
      $cover = $_FILES['cover'];

      $fileName = $cover['name'];
      $fileType = $cover['type'];
      $fileTempName = $cover['tmp_name'];
      $fileError = $cover['error'];
      $fileSize = $cover['size'];

      $fileExt = explode('.', $fileName);
      $fileActualExt = strtolower(end($fileExt));

      // Dozwolone rozszerzenia plików
      $allowed = array("jpg", "jpeg", "png");

      // Pętlą sprawdzającą czy dana nazwa kategorii jest zajęta
      $taken = false;
      $stmt = $dbh->prepare("SELECT title FROM categories");
      $stmt ->execute();  
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if(strtolower($title) == strtolower($row['title'])){
          $taken = true;
        }
      }

      // Sprawdzanie różnych warunków wprowadzanych danych (polskie znaki, rozszerzenie pliku, rozmiar itp.)
      if(preg_match('#^[a-zA-Z0-9\-\_ąćęłńóśźżĄĆĘŁŃÓŚŹŻ ]*$#', $title)){
        if(!$taken){
          if (in_array($fileActualExt, $allowed)){
            if ($fileError === 0) {
              if ($fileSize < 5200000) {
                $coverFullName = $titleModified . "." . uniqid("", true) . "." . $fileActualExt;
                $coverDestination = "covers/" . $coverFullName;

                // Jeśli wszystko się zgadza to dodajemy dane do bazy, a zdjęcie ląduje do oddzielnego folderu po stronie serwera (/public_html/covers)
                $stmt = $dbh->prepare("INSERT INTO categories (title, description, coverFullName) VALUES (:title, :description, :coverFullName)");
                $stmt ->execute([':title' => $title, ':description' => $description, ':coverFullName' => $coverFullName]);
                move_uploaded_file($fileTempName, $coverDestination);
                echo("<script>location.href = '/';</script>");
              } else {
                print '<p style="font-weight: bold; color: red;">Your file is too big. Max size: 5.2MB</p>';
                echo("<script>location.href = '/#div-new-cat';</script>");
              }
            } else {
              print '<p style="font-weight: bold; color: red;">There was an error uploading your file.</p>';
              echo("<script>location.href = '/#div-new-cat';</script>");
            }
          } else {
            print '<p style="font-weight: bold; color: red;">Invalid file type. Please make sure your file is jpg, jpeg or png extension.</p>';
            echo("<script>location.href = '/#div-new-cat';</script>");    
          }
        } else {
          print '<p style="font-weight: bold; color: red;">Chosen title already in use. Choose another one or check out the already existing one.</p>';
          echo("<script>location.href = '/#div-new-cat';</script>");        
        }
      } else {
        print '<p style="font-weight: bold; color: red;">Only numbers and Polish letters are allowed in category title.</p>';
        echo("<script>location.href = '/#div-new-cat';</script>");
      }
    }

?>
    <!-- Formularz do dodawania kategorii -->
    <form action="" method="POST" id="cat-form" enctype="multipart/form-data">
      <div class="form-group">
        <input type="text" name="title" class="form-control input-cat" required="required" placeholder="Category title">
      </div>
      
      <div class="form-group">
        <textarea class="form-control input-cat" name="description" rows="3" required="required" placeholder="Category description"></textarea>
      </div>

      <div class="form-group">
        <label for="cover" style="font-size: 20px;">Select a cover photo for this category</label>
        <input type="file" name="cover" id="cover" class="form-control dropzone input-cat" required="required">
      </div>
      
      <button type="submit" name="submit" class="btn btn-light">Add category</button>

    </form>
  </div>
</div>
