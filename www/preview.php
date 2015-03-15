<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');
  
   //Text labels here
   define('BTN_DOWNLOAD', 'Download');
   define('BTN_DELETE', 'Delete');
   define('BTN_DELETEALL', 'Delete All');
   define('BTN_DELETESEL', 'Delete Sel');
   define('BTN_SELECTALL', 'Select All');
   define('BTN_SELECTNONE', 'Select None');
   define('BTN_GETZIP', 'Get Zip');
   define('BTN_UPDATESIZES', 'Update Sizes');
   define('TXT_PREVIEW', 'Preview');
   define('TXT_THUMB', 'Thumb');
   define('TXT_FILES', 'Files');
   
   //Set size defaults and try to get from cookies
   $previewSize = 640;
   $thumbSize = 96;
   if(isset($_COOKIE["previewSize"])) {
      $previewSize = $_COOKIE["previewSize"];
   }
   if(isset($_COOKIE["thumbSize"])) {
      $thumbSize = $_COOKIE["thumbSize"];
   }
   $dSelect = "";
   $pFile = "";
   $tFile = "";
   $debugString = "";
   
   if(isset($_GET['preview'])) {
      $tFile = $_GET['preview'];
      $pFile = substr($tFile, 0, -13);
   }
   
   //Process any POST data
   // 1 file based commands
   if ($_POST['delete1']) {
      deleteFile($_POST['delete1']);
      //deleteOrphans();
   } else if ($_POST['download1']) {
      $dFile = $_POST['download1'];
      if(substr($dFile, -12, 1) == 't') {
         $zipname = getZip(array($dFile));
         header("Content-Type: application/zip");
         header("Content-Disposition: attachment; filename=\"" . $zipname . "\"");
         readfile("$zipname");
         if(file_exists($zipname)){
             unlink($zipname);
         }                  
         return;
      } else {
         $dFile = substr($dFile, 0, -13);
         if(substr($dFile, -16, 3) == "jpg") {
            header("Content-Type: image/jpeg");
         } else {
            header("Content-Type: video/mp4");
         }
         header("Content-Disposition: attachment; filename=\"" . $dFile . "\"");
         readfile(MEDIA_PATH . "/$dFile");
         return;
      }
   } else {
      //global commands
      switch($_POST['action']) {
         case 'deleteAll':
            $files = scandir(MEDIA_PATH);
            foreach($files as $file) unlink(MEDIA_PATH . "/$file");
            break;
         case 'selectAll':
            $dSelect = "checked";
            break;
         case 'selectNone':
            $dSelect = "";
            break;
         case 'deleteSel':
            if(!empty($_POST['check_list'])) {
               foreach($_POST['check_list'] as $check) {
                  deleteFile($check);
               }
            }        
            //deleteOrphans();
            break;
         case 'zipSel':
            if(!empty($_POST['check_list'])) {
               $zipname = getZip($_POST['check_list']);
               header("Content-Type: application/zip");
               header("Content-Disposition: attachment; filename=\"" . $zipname . "\"");
               readfile("$zipname");
               if(file_exists($zipname)){
                   unlink($zipname);
               }                  
            }        
            break;
         case 'updateSizes':
            if(!empty($_POST['previewSize'])) {
               $previewSize = $_POST['previewSize'];
               if ($previewSize < 100 || $previewSize > 1920) $previewSize = 640;
               setcookie("previewSize", $previewSize, time() + (86400 * 365), "/");
            }        
            if(!empty($_POST['thumbSize'])) {
               $thumbSize = $_POST['thumbSize'];
               if ($thumbSize < 32 || $thumbSize > 320) $thumbSize = 96;
               setcookie("thumbSize", $thumbSize, time() + (86400 * 365), "/");
            }        
            break;
      }
   }
   
   function getZip($files) {
      $zipname = MEDIA_PATH . '/cam_' . date("Ymd_His") . '.zip';
      $zip = new ZipArchive;
      $zip->open($zipname, ZipArchive::CREATE);
      foreach($files as $file) {
         if (substr($file, -12, 1) == 't') {
            $lapses = findLapseFiles($file);
            if (!empty($lapses)) {
               foreach($lapses as $lapse) {
                  $zip->addFile(MEDIA_PATH . "/$lapse");
               }
            }
         } else {
            $base = substr($file, 0 , -13);
            if (file_exists(MEDIA_PATH . "/$base")) {
               $zip->addFile(MEDIA_PATH . "/$base");
            }
         }
      }
      $zip->close();
      return $zipname;
   }
   
   function findLapseFiles($d) {
      //return an arraranged in time order and then must have a matching 4 digit batch and an incrementing lapse number
      $batch = sprintf('%04d', substr($d, -11, 4));
      $start = filemtime(MEDIA_PATH . "/$d");
      $files = array();
      $scanfiles = scandir(MEDIA_PATH);
      $lapsefiles = array();
      foreach($scanfiles as $file) {
         if (strpos($file, $batch) !== false) {
            if (strpos($file, '.th.jpg') === false) {
               $fDate = filemtime(MEDIA_PATH ."/$file");
               if ($fDate >= $start) {
                  $files[$file] = $fDate;
               }
            }
         }
      }
      $debugString .= ' find1 ' . count($files) . '<BR>';
      asort($files);
      $lapseCount = 1;
      foreach($files as $key => $value) {
         if (strpos($key, sprintf('%04d', $lapseCount)) !== false) {
            $lapsefiles[] = $key;
            $lapseCount++;
         } else {
            break;   
         }
      }
      return $lapsefiles;
   }

   //function to delete all files associated with a thumb name
   function deleteFile($d) {
      $t = substr($d,-12, 1); 
      if ($t == 't') {
         // For time lapse try to delete all from this batch
         
         //get file list in time order
         $files = findLapseFiles($d);
         foreach($files as $file) {
            unlink(MEDIA_PATH . "/$file");
         }
      } else {
         $tFile = substr($d, 0, -13);
         if (file_exists(MEDIA_PATH . "/$tFile")) {
            unlink(MEDIA_PATH . "/$tFile");
         }
      }
      unlink(MEDIA_PATH . "/$d");
   }
   
   //function to check for and delete orphan files without a thumb files
   function deleteOrphans() {
      $files = scandir(MEDIA_PATH);
      $thumbs = array();
      foreach($files as $file) {
         if (substr($file,-7) == '.th.jpg') {
            $thumbs[] = substr($file,0,-7);
         }
      }
      foreach($files as $file) {
         if (substr($file,-7) != '.th.jpg') {
            if (!in_array($thumb, $file)) {
               unlink(MEDIA_PATH . "/$file");
            }
         }
      }
   }

   //function to draw 1 file on the page
   function drawFile($f, $ts, $sel) {
      $fType = substr($f,-12, 1);
      $rFile = substr($f, 0, -13);
      $fNumber = substr($f,-11,4);
      $lapseCount = "";
      switch ($fType) {
         case 'v': $fIcon = 'video.png'; break;
         case 't': 
            $fIcon = 'timelapse.png';
            $lapseCount = '(' . count(findLapseFiles($f)). ')';
            break;
         case 'i': $fIcon = 'image.png'; break;
         default : $fIcon = 'image.png'; break;
      }
      $fsz = round ((filesize(MEDIA_PATH . "/$rFile")) / 1024);
      $fModTime = filemtime(MEDIA_PATH . "/$rFile");
      $fDate = date('Y-m-d', $fModTime);
      $fTime = date('H:i:s', $fModTime);
      $fWidth = max($ts + 4, 140);
      echo "<fieldset class='fileicon' style='width:" . $fWidth . "px;'>";
      echo "<legend class='fileicon'>";
      echo "<button type='submit' name='delete1' value='$f' class='fileicondelete' style='background-image:url(delete.png);
'></button>";
      echo "&nbsp;&nbsp;$fNumber&nbsp;";
      echo "<img src='$fIcon' style='width:24px'/>";
      echo "<input type='checkbox' name='check_list[]' $sel value='$f' style='float:right;'/>";
      echo "</legend>";
      echo "$fsz Kb $lapseCount";
      echo "<br>$fDate<br>$fTime";
      echo "<br><a title='$rFile' href='preview.php?preview=$f'>";
      echo "<img src='" . MEDIA_PATH . "/$f' style='width:" . $ts . "px'/>";
      echo "</a>";
      echo "</fieldset> ";
   }
?>
<!DOCTYPE html>
<html>
   <head>
      <meta name="viewport" content="width=550, initial-scale=1">
      <title>RPi Cam Download</title>
      <link rel="stylesheet" href="css/style_minified.css" />
      <link rel="stylesheet" href="css/preview.css" />
      <link rel="stylesheet" href="css/extrastyle.css" />
      <script src="js/style_minified.js"></script>
   </head>
   <body>
      <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
         <div class="container">
            <div class="navbar-header">
               <a class="navbar-brand" href="index.php"><span class="glyphicon glyphicon-chevron-left"></span>Back - <?php echo CAM_STRING; ?></a>
            </div>
         </div>
      </div>
    
      <div class="container-fluid">
      <form action="preview.php" method="POST">
      <?php
         if ($pFile != "") {
            echo "<h1>" . TXT_PREVIEW . ":  " . substr($pFile,0,10);
            echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='download1' value='$tFile'>" . BTN_DOWNLOAD . "</button>";
            echo "&nbsp;<button class='btn btn-primary' type='submit' name='delete1' value='$tFile'>" . BTN_DELETE . "</button></p>";
            echo "</h1>";
            if(substr($pFile, -3) == "jpg") {
               echo "<a href='" . MEDIA_PATH . "/$tFile' target='_blank'><img src='" . MEDIA_PATH . "/$pFile' width='" . $previewSize . "px'></a>";
            } else {
               echo "<video width='" . $previewSize . "px' controls><source src='" . MEDIA_PATH . "/$pFile' type='video/mp4'>Your browser does not support the video tag.</video>";
            }
         }
         echo "<h1>" . TXT_FILES . "&nbsp;&nbsp;";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='selectNone'>" . BTN_SELECTNONE . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='selectAll'>" . BTN_SELECTALL . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='zipSel'>" . BTN_GETZIP . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='action' value='deleteSel'>" . BTN_DELETESEL . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='action' value='deleteAll'>" . BTN_DELETEALL . "</button>";
         echo "</h1><br>";
         if ($debugString !="") echo "$debugString<br>";
         $files = scandir(MEDIA_PATH);
         if(count($files) == 2) echo "<p>No videos/images saved</p>";
         else {
            foreach($files as $file) {
               if(($file != '.') && ($file != '..') && (substr($file, -7) == '.th.jpg')) {
                  drawFile($file, $thumbSize, $dSelect);
               } 
            }
         }
         echo "<p><p>" . TXT_PREVIEW . " <input type='text' size='4' name='previewSize' value='$previewSize'>";
         echo "&nbsp;&nbsp;" . TXT_THUMB . " <input type='text' size='3' name='thumbSize' value='$thumbSize'>";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='updateSizes'>" . BTN_UPDATESIZES . "</button>";
      ?>
      </form>
      </div>
   </body>
</html>
