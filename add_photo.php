<!-- Dodawanie nowych zdjęć -->
<div class="row">
 	  <div id="div-new-photo">
    <p style="font-size: 28px;">add your photos to any category</p>
    <i class="icon-down-open"></i>
  </div>
  <br/>

  <div id="div-photo-form">

<?php
    if(isset($_POST['submit'])){
      $photoTitle = $_POST['photoTitle'];

      // Ustalanie nazwy zdjecia do zapisu w folderze
      if(empty($photoTitle)){
        $titleModified = "unnamedCategory";
      } else {
        $titleModified = strtolower(str_replace(" ", "-", $photoTitle));
      }

      $photoDescription = $_POST['photoDescription'];
      $photoCategory = $_POST['photoCategory'];
      
      // Sprawdzanie wybrania kategorii, do której chcemy dodać zdjęcie
      $catChosen = false;  
      if($photoCategory != 'unchosen'){
        $catChosen = true;
        $catTitleModified = strtolower(str_replace(" ", "-", $photoCategory));
      }

      $photo = $_FILES['photo'];

      $fileName = $photo['name'];
      $fileType = $photo['type'];
      $fileTempName = $photo['tmp_name'];
      $fileError = $photo['error'];
      $fileSize = $photo['size'];

      $fileExt = explode('.', $fileName);
      $fileActualExt = strtolower(end($fileExt));

      // Dozwolone rozszerzenia plików
      $allowed = array("jpg", "jpeg", "png");

      // Pętlą sprawdzającą czy dana nazwa zdjęcia w danej kategorii jest zajęta
      $taken = false;
      $stmt = $dbh->prepare("SELECT title FROM photos WHERE category = :category");
      $stmt ->execute([":category" => $photoCategory]);  
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if(strtolower($photoTitle) == strtolower($row['title'])){
          $taken = true;
        }
      }

      // Sprawdzanie różnych warunków wprowadzanych danych (rozszerzenie pliku, rozmiar, czy wybrano kategorię itp.)
      if(!$taken){
        if($catChosen){
          if (in_array($fileActualExt, $allowed)){
            if ($fileError === 0) {
              if ($fileSize < 5200000) {
                $photoFullName = $titleModified . "." . uniqid("", true) . "." . $fileActualExt;
                $photoDestination = "photos/" . $photoFullName;

                // Jeśli wszystko się zgadza to dodajemy dane do bazy, a zdjęcie ląduje do oddzielnego folderu po stronie serwera (/public_html/photos)
                $stmt2 = $dbh->prepare("INSERT INTO photos (title, description, photoFullName, category) VALUES (:title, :description, :photoFullName, :category)");
                $stmt2 ->execute([':title' => $photoTitle, ':description' => $photoDescription, ':photoFullName' => $photoFullName, ':category' => $photoCategory]);
                move_uploaded_file($fileTempName, $photoDestination);

                // Przekierowanie na podstronę kategorii, do której właśnie dodaliśmy zdjęcie
                echo("<script>location.href = '/category/". $catTitleModified ."';</script>");
              } else {
                print '<p style="font-weight: bold; color: red;">Your file is too big. Max size: 5.2MB</p>';
              }
            } else {
              print '<p style="font-weight: bold; color: red;">There was an error uploading your file.</p>';
            }
          } else {
            print '<p style="font-weight: bold; color: red;">Invalid file type. Please make sure your file is jpg, jpeg or png extension.</p>';
          }
        } else {
          print '<p style="font-weight: bold; color: red;">Choose a category!</p>';         
        }
      } else {
        print '<p style="font-weight: bold; color: red;">Chosen title already in use. Choose another one.</p>';
      }
    }

?>    
    <!-- Formularz do dodawania zdjęć -->
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <input type="text" name="photoTitle" class="form-control input-photo" required="required" placeholder="Photo title">
      </div>
      
      <div class="form-group">
        <textarea class="form-control input-photo" name="photoDescription" rows="3" required="required" placeholder="Photo description"></textarea>
      </div>

      <div class="form-group">
	      <select class="form-control input-photo" name="photoCategory">
				  <option selected value="unchosen">Choose a category</option>
<?php
          // Lista wybieralna dostępnych kategorii
				  $stmt = $dbh->prepare("SELECT title FROM categories");
				  $stmt ->execute();

				  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
				  	print '<option value="'. $title .'">'. $title .'</option>';
				  };	   
?>
				</select>
      </div>

      <div class="form-group">
        <label for="photo" style="font-size: 20px;">Select a photo for chosen category </label>
        <input type="file" name="photo" id="photo" class="form-control dropzone input-photo" required="required">
      </div>
      
      <button type="submit" name="submit" class="btn btn-light">Add photo</button>
    </form>

  </div>
</div>