<!DOCTYPE html>
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
   
   if(isset($_GET['preview'])) {
      $pFile = $_GET['preview'];
   }
   
   //Process any POST data
   // 1 file based commands
   if ($_POST['delete1']) {
      unlink("media/" . $_POST['delete1']);
      $tFile = getThumb($_POST['delete1'], false);
      if ($tFile != "") {
         unlink("media/$tFile");
      }
   } else if ($_POST['download1']) {
      $dFile = $_POST['download1'];
      if(substr($dFile, -3) == "jpg") {
         header("Content-Type: image/jpeg");
      } else {
         header("Content-Type: video/mp4");
      }
      header("Content-Disposition: attachment; filename=\"" . $dFile . "\"");
      readfile("media/$dFile");
   } else if ($_POST['preview']) {
      $pFile = $_POST['preview'];
   } else {
      //global commands
      switch($_POST['action']) {
         case 'deleteAll':
            $files = scandir("media");
            foreach($files as $file) unlink("media/$file");
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
                  unlink("media/$check");
                  $tFile = getThumb($check, false);
                  if ($tFile != "") {
                     unlink("media/$tFile");
                  }
               }
            }        
            break;
         case 'zipSel':
            if(!empty($_POST['check_list'])) {
               $zipname = 'media/cam_' . date("Ymd_His") . '.zip';
               $zip = new ZipArchive;
               $zip->open($zipname, ZipArchive::CREATE);
               foreach($_POST['check_list'] as $check) {
                  $zip->addFile("media/$check");
               }
               $zip->close();
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
   
   //function to search for matching thumb files, within 4 seconds back for motion triggered videos
   function getThumb($vFile, $makeit) {
      $fType = substr($vFile,0,5);
      $fDate = substr($vFile,11,8);
      $fTime = substr($vFile,20,8);
      if ($fType == 'video') {
         for ($i = 0; $i < 4; $i++) {
            $thumb = 'vthumb_' . $fDate . '_' . sprintf('%06d', $fTime - $i) . '.jpg';
            if (file_exists("media/$thumb")) {
               return $thumb;
            }
         }
         //run command to generate video thumb
         if ($makeit) {
            $thumb = 'vthumb_' . $fDate . '_' . sprintf('%06d', $fTime) . '.jpg';
            exec("ffmpeg -i media/$vFile -vframes 1 -r 1 -s 162x122 -f image2 media/$thumb");
            return $thumb;
         }
      }
      else if ($fType == 'image') {
         $thumb = 'ithumb_' . $fDate . '_' . sprintf('%06d', $fTime - $i) . '.jpg';
         if (file_exists("media/$thumb")) {
            return $thumb;
         }
         else if ($makeit) {
            //run command for image
            exec("ffmpeg -i media/$vFile -vframes 1 -r 1 -s 162x122 -f image2 media/$thumb");
            return $thumb;
         }
      }
      return "";
   }

   //function to draw 1 file on the page
   function drawFile($f, $ts, $sel) {
      $fsz = round ((filesize("media/" . $f)) / 1024);
      $fType = substr($f,0,5);
      $fNumber = substr($f,6,4);
      $fDate = substr($f,11,8);
      $fTime = substr($f,20,8);
      echo "<fieldset class='fileicon'>";
      echo "<legend class='fileicon'>";
      echo "<button type='submit' name='delete1' value='$f' class='fileicondelete' style='background-image:url(delete.png);
'></button>";
      echo "&nbsp;&nbsp;$fNumber";
      echo "&nbsp;&nbsp;<input type='checkbox' name='check_list[]' $sel value='$f' style='float:right;'>";
      echo "</legend>";
      echo "$fsz Kb";
      echo "<br>" . substr($fDate,0,4) . "-" . substr($fDate,4,2) . "-" . substr($fDate,6,2);
      echo "<br>" .substr($fTime,0,2) . ":" . substr($fTime,2,2) . ":" . substr($fTime,4,2);
      $tFile = getThumb($f, true);
      if($tFile != "") {
         echo "<br><a href='preview.php?preview=$f'>";
         echo "<img src='media/$tFile' style='width:" . $ts . "px'/>";
         echo "</a>";
      }
      else { 
         echo "None";
      }
      echo "</fieldset> ";
   }
?>

<html>
   <head>
      <meta name="viewport" content="width=550, initial-scale=1">
      <title>RPi Cam Download</title>
      <link rel="stylesheet" href="css/style_minified.css" />
      <link rel="stylesheet" href="css/preview.css" />
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
            echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='download1' value='$pFile'>" . BTN_DOWNLOAD . "</button>";
            echo "&nbsp;<button class='btn btn-primary' type='submit' name='delete1' value='$pFile'>" . BTN_DELETE . "</button></p>";
            echo "</h1>";
            if(substr($pFile, -3) == "jpg") {
               echo "<a href='media/$tFile' target='_blank'><img src='media/$pFile' width='" . $previewSize . "px'></a>";
            } else {
               echo "<video width='" . $previewSize . "px' controls><source src='media/$pFile' type='video/mp4'>Your browser does not support the video tag.</video>";
            }
         }
         echo "<h1>" . TXT_FILES . "&nbsp;&nbsp;";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='selectNone'>" . BTN_SELECTNONE . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='selectAll'>" . BTN_SELECTALL . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-primary' type='submit' name='action' value='zipSel'>" . BTN_GETZIP . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='action' value='deleteSel'>" . BTN_DELETESEL . "</button>";
         echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='action' value='deleteAll'>" . BTN_DELETEALL . "</button>";
         echo "</h1><br>";
         $files = scandir("media");
         if(count($files) == 2) echo "<p>No videos/images saved</p>";
         else {
            foreach($files as $file) {
               if(($file != '.') && ($file != '..') && ((substr($file, 0, 5) == 'video') || (substr($file, 0, 5) == 'image'))) {
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
