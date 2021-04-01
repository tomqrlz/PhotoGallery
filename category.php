<?php
if (!defined('IN_INDEX')) { exit("Nie można uruchomić tego pliku bezpośrednio."); }

// Podstrona wyświetlająca miniaturki zdjęć z danej kategorii (miniaturki są o określonych wymiarach i wycinają centralny fragment zdjęcia)
if(isset($_GET['cat'])){

  $cat = $_GET['cat'];
  $stmt = $dbh->prepare("SELECT * FROM categories");
  $stmt ->execute();

  print '<div id="div-gallery">';

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $title = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
    $titleModified = strtolower(str_replace(" ", "-", $title));

    // Pobranie wszystkich zdjęć tylko z danej kategorii
    if ($titleModified == $cat) {
      $GLOBALS['cat_id'] = $row['id'];
      $GLOBALS['cat_name'] = $cat;

      // Pobranie zdjęć tylko z danej kategorii w kolejności zależnej od dodania (najnowsze zdjęcia pojawiają się na górze)
      $stmt2 = $dbh->prepare("SELECT * FROM photos WHERE category = :category ORDER BY id DESC");
      $stmt2->execute([":category" => $row['title']]);

      while($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)){ 
        $photoId = $row2['id'];
        $photoTitle = htmlspecialchars($row2['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
        $photoDescription = htmlspecialchars($row2['description'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
        $photoFullName = htmlspecialchars($row2['photoFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
        
        // Zabezpieczenie przed pobieraniem poprzez ustawienie zdjęcia jako tło diva oraz nałożenie przeźroczystej powłoki
        print '<div class="gallery-container" >
                <a href="/category/'. $cat .'/'. $photoId .'" class="gallery-photo-link">
                  <div class="gallery-photo" style="background-image: url(/photos/'. $photoFullName .');">
                    <div class="photo-text-overlay"> Click on the photo to check out the details or to edit. </div>
                  </div>
                </a>
                <div class="gallery-caption">
                  <h4>'. $photoTitle .'</h4>
                  <p style="font-size: 16px;">'. $photoDescription .'</p>
                </div>
              </div>';
      }
    }
  }
  print '</div>';
} 
  // Podstrona wyświetlająca wybrane zdjęcie w pełnym rozmiarze
  elseif (isset($_GET['photo']) && intval($_GET['photo']) > 0) {
	$id = intval($_GET['photo']);

  // Pobranie danych wybranego zdjęcia
	$stmt = $dbh->prepare("SELECT * FROM photos WHERE id=:id");
  $stmt ->execute([":id" => $id]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $photoTitle = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $photoCategory = htmlspecialchars($row['category'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

  $stmt2 = $dbh->prepare("SELECT title FROM photos WHERE category = :category AND id != :id");
  $stmt2 ->execute([":category" => $photoCategory, ":id" => $id]);  

  // Obsługa formularza edytującego podpisy
  if(isset($_POST['submit'])) {
  	$photoTitleEdited = $_POST['photoTitleEdited'];
  	$photoDescriptionEdited = $_POST['photoDescriptionEdited'];
	  
    // Pętlą sprawdzającą czy edytowana nazwa zdjęcia jest zajęta
	  $taken = false;
	  while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
	    if(strtolower($photoTitleEdited) == strtolower($row2['title'])){
	      $taken = true;
	    }
	  }
	  $GLOBALS['taken'] = $taken;

    // Jeśli nazwa nie jest zajęta to uaktualniamy podpisy zdjęcia
	  if(!$taken){
	  	$edit = $dbh->prepare("UPDATE photos SET title = :title, description = :description WHERE id = :id");
	    $edit->execute([':title' => $photoTitleEdited, ':description' => $photoDescriptionEdited, ':id' => $id]);
	  } 
	}

  // Pobranie aktualnych danych zdjęcia
	$stmt = $dbh->prepare("SELECT * FROM photos WHERE id=:id");
  $stmt ->execute([":id" => $id]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $photoTitle = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $photoDescription = htmlspecialchars($row['description'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $photoCategory = htmlspecialchars($row['category'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $photoFullName = htmlspecialchars($row['photoFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

  // Wyświetlenie podpisów zdjęcia
  print '<div class="row">
  				<div class="col-sm" style="color: #ebebeb; text-align: center;">
  					<h2>'. $photoTitle .'</h2>
  					<p style="word-wrap: break-word;">'. $photoDescription .' </p>
  				</div>
  			</div>';

  // Wyświetlenie zdjęcia w pełnym rozmiarze (zabezpieczenie przed pobieraniem poprzez ustawienie zdjęcia jako tło i niewidzialnej powłoki [chrome,IE,mozilla] + usunięcie menu kontekstowego [mozilla])
	print '<div class="div-plain-photo" oncontextmenu="return false" style="background-image: url(/photos/'. $photoFullName .');">
					<div class="plain-photo-overlay">
            <img class="plain-photo" style="visibility: hidden;" src="/photos/'. $photoFullName .'"/>
          </div>
				</div>';

  // Formularz do edycji podpisów zdjęcia
	print '<br/>
				<div class="row">
			 	  <div id="div-edit-photo">
				    <p style="font-size: 28px;">edit this photo</p>
				    <i class="icon-down-open"></i>
				  </div>
				  <br/>

  				<div id="div-edit-form">';
	
  if(isset($_POST['submit'])) {
	  if($GLOBALS['taken']){
	  	print '<p style="font-weight: bold; color: red;">Chosen title already in use. Choose another one.</p>';
  	}
	}

	print	    '<form action="" method="POST">
				      <div class="form-group">
				        <input type="text" name="photoTitleEdited" class="form-control input-edit" placeholder="Photo title" required="required" value="'. $photoTitle .'">
				      </div>
				      
				      <div class="form-group">
				        <textarea name="photoDescriptionEdited" class="form-control input-edit" rows="3" placeholder="Photo description" required="required">'. $photoDescription .'</textarea>
				      </div>
				      
				      <button type="submit" name="submit" class="btn btn-light">Edit photo</button>
				    </form>
  				</div>
  			</div>';

} 
  // Podstrona do usuwania kategorii
  elseif (isset($_GET['delete']) && intval($_GET['delete']) > 0) {
  $id = intval($_GET['delete']);

  // Pobranie danej kategorii w celu usunięcia okładki oraz zdjęć z usuwanej kategorii (najpierw usuwamy wszystkie jej zdjęcia, a na końcu ją samą)
  $stmt = $dbh->prepare("SELECT * FROM categories WHERE id=:id");
  $stmt->execute([":id" => $id]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $category = htmlspecialchars($row['title'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $coverFullName = htmlspecialchars($row['coverFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

  // Usunięcie zdjęcia (okładki) usuwanej kategorii z folderu po stronie serwera (/public_html/covers)
  $coverLocation = "covers/" . $coverFullName;
  if($coverLocation != "covers/"){
  	unlink($coverLocation);
  }
  
  // Pobranie zdjęć	z usuwanej kategorii
  $stmt2 = $dbh->prepare("SELECT * FROM photos WHERE category = :category");
  $stmt2->execute([":category" => $category]);

  // Usunięcie wszystkich zdjęć (z usuwanej kategorii) z folderu po stronie serwera (/public_html/photos)
  while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $photoFullName = htmlspecialchars($row2['photoFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  	$photoLocation = "photos/" . $photoFullName;
   	if($photoLocation != "photos/"){
		 	unlink($photoLocation);
 		}
  }

  // Usuwanie danych wszystkich zdjęć (z usuwanej kategorii) z bazy danych
  $delPhotos = $dbh->prepare("DELETE FROM photos WHERE category = :category");
  $delPhotos->execute([":category" => $category]);

  // Usunięcie danych usuwanej kategorii z bazy danych
  $delCat = $dbh->prepare("DELETE FROM categories WHERE id = :id");
  $delCat->execute([":id" => $id]);

  // Powrót na stronę główną
  echo("<script>location.href = '/';</script>");

} 
  // Podstrona do usuwania poszczególnych zdjęć
  elseif(isset($_GET['photodelete']) && intval($_GET['photodelete']) > 0) {
	$photoId = intval($_GET['photodelete']);

  // Pobranie danych usuwanego zdjęcia
	$stmt = $dbh->prepare("SELECT * FROM photos WHERE id = :id");
  $stmt->execute([":id" => $photoId]);

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $category = htmlspecialchars($row['category'], ENT_QUOTES | ENT_HTML401, 'UTF-8');
  $catTitleModified = strtolower(str_replace(" ", "-", $category));
  $photoFullName = htmlspecialchars($row['photoFullName'], ENT_QUOTES | ENT_HTML401, 'UTF-8');

  // Usunięcie zdjęcia z folderu po stronie serwera (/public_html/photos)
 	$photoLocation = "photos/" . $photoFullName;
 	if($photoLocation != "photos/"){
	 	unlink($photoLocation);
 	}

  // Usunięcie danych zdjęcia z bazy danych
	$stmt2 = $dbh->prepare("DELETE FROM photos WHERE id=:id");
  $stmt2 ->execute([":id" => $photoId]);

  // Powrót na podstronę kategorii
  echo("<script>location.href = '/category/". $catTitleModified ."';</script>");
}

// Opcje pojawiające się na każdej podstronie /category/
// Przycisk powrotu do podstrony kategorii
if(isset($_GET['photo'])){
  print '<div class="row">
          <div class="col-sm" style="text-align: left;">
            <a href="/category/'. $_GET['categ'] .'" class="cat-options-link"><span><i class="icon-left-open"></i>Go back</span></a>
          </div>';
} 
// Przycisk powrotu do strony głównej
else {
  print '<div class="row">
          <div class="col-sm" style="text-align: left;">
            <a href="/" class="cat-options-link"><span><i class="icon-left-open"></i>Go back to the main page</span></a>
          </div>';
}
// Przycisk usunięcia kategorii
if(isset($_GET['cat'])){
  print '<div class="col-sm" style="text-align: center;">
          <a href="/category/delete/'. $GLOBALS['cat_id'] .'" class="cat-options-link"><span><i class="icon-trash"></i>Delete this category</span></a>
        </div>';
}
// Przycusk do usunięcia zdjęcia
if(isset($_GET['photo'])){
  print '<div class="col-sm" style="text-align: center;">
          <a href="/category/'. $_GET['categ'] .'/delete/'. intval($_GET['photo']) .'" class="cat-options-link"><span><i class="icon-trash"></i>Delete this photo</span></a>
        </div>';
}
// Przycisk do dodania zdjęcia
print   '<div class="col-sm" style="text-align: right;">
          <a href="/add_photo" class="cat-options-link"><span>Add your own photo<i class="icon-right-open"></i></span></a>
        </div>
      </div>';
?>

<!-- <script>
function catDeleteAssurance() {
  if (confirm("Are you sure you want to delete this category?")) {
    alert("hello");
  } else{
    alert("no")
  }
}
</script>
 -->