<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');
  
   define('SUBDIR_CHAR', '@');

   //Text labels here
   define('BTN_DOWNLOAD', 'Download');
   define('BTN_DELETE', 'Delete');
   define('BTN_CONVERT', 'Start Convert');
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
      $pFile = dataFilename($tFile);
   }
   
   //Process any POST data
   // 1 file based commands
   if ($_POST['delete1']) {
      deleteFile($_POST['delete1']);
      maintainFolders(MEDIA_PATH, false, false);
   } else if ($_POST['convert']) {
      $tFile = $_POST['convert'];
      startVideoConvert($tFile);
      $tFile = "";
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
         $dxFile = dataFilename($dFile);
         if(substr($dFile, -16, 3) == "jpg") {
            header("Content-Type: image/jpeg");
         } else {
            header("Content-Type: video/mp4");
         }
         header("Content-Disposition: attachment; filename=\"" . substr($dFile,0,-13) . "\"");
         readfile(MEDIA_PATH . "/$dxFile");
         return;
      }
   } else {
      //global commands
      switch($_POST['action']) {
         case 'deleteAll':
            maintainFolders(MEDIA_PATH, true, true);
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
            maintainFolders(MEDIA_PATH, false, false);
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
   
   function dataFilename($file) {
      return str_replace(SUBDIR_CHAR, '/', substr($file, 0 , -13));
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
                  $zip->addFile($lapse);
               }
            }
         } else {
            $base = dataFilename($file);
            if (file_exists(MEDIA_PATH . "/$base")) {
               $zip->addFile(MEDIA_PATH . "/$base");
            }
         }
      }
      $zip->close();
      return $zipname;
   }
   
   function startVideoConvert($bFile) {
      global $debugString;
      $tFiles = findLapseFiles($bFile);
      $tmp = BASE_DIR . '/' . MEDIA_PATH . '/' . substr($bFile, -12, 5);
      if (!file_exists($tmp)) {
         mkdir($tmp, 0777, true);
      }
      $i= 1;
      foreach($tFiles as $tFile) {
         rename($tFile, $tmp . '/' . sprintf('i_%05d', $i) . '.jpeg');
         $i++;
      }
      $vFile = substr(dataFilename($bFile), 0, -3) . 'mp4';
      exec('avconv -i ' . "$tmp/i_%05d.jpeg -r 5 -vcodec libx264 -crf 20 -g 5 " . BASE_DIR . '/' .MEDIA_PATH . "/$vFile");
      $tFiles = scandir($tmp);
      foreach($tFiles as $tFile) {
         unlink("$tmp/$tFile");
      }
      rmdir($tmp);
      $vFile .= '.v' . substr($bFile, -11);
      rename(MEDIA_PATH . "/$bFile", MEDIA_PATH . "/$vFile");
   }
   
   function findLapseFiles($d) {
      //return an arranged in time order and then must have a matching 4 digit batch and an incrementing lapse number
      $batch = sprintf('%04d', substr($d, -11, 4));
      $fullname = MEDIA_PATH . '/' . dataFilename($d);
      $path = dirname($fullname);
      $start = filemtime("$fullname");
      $files = array();
      $scanfiles = scandir($path);
      $lapsefiles = array();
      foreach($scanfiles as $file) {
         if (strpos($file, $batch) !== false) {
            if (strpos($file, '.th.jpg') === false) {
               $fDate = filemtime("$path/$file");
               if ($fDate >= $start) {
                  $files[$file] = $fDate;
               }
            }
         }
      }
      asort($files);
      $lapseCount = 1;
      foreach($files as $key => $value) {
         if (strpos($key, sprintf('%04d', $lapseCount)) !== false) {
            $lapsefiles[] = "$path/$key";
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
            if(!unlink($file)) $debugString .= "F ";
         }
      } else {
         $tFile = dataFilename($d);
         if (file_exists(MEDIA_PATH . "/$tFile")) {
            unlink(MEDIA_PATH . "/$tFile");
         }
      }
      unlink(MEDIA_PATH . "/$d");
   }

   // function to deletes files and folders recursively
   // $deleteMainFiles true r false to delete files from the top level folder
   // $deleteSubFiles true or false to delete files from subfolders
   // Empty subfolders get removed.
   // $root true or false. If true (default) then top dir not removed
   function maintainFolders($path, $deleteMainFiles, $deleteSubFiles, $root = true) {
      $empty=true;
      foreach (glob("$path/*") as $file) {
         if (is_dir($file)) {
            if (!maintainFolders($file, $deleteMainFiles, $deleteSubFiles, false)) $empty=false;
         }  else {
            if (($deleteSubFiles && !$root) || ($deleteMainFiles && $root)) {
              unlink($file);
            } else {
               $empty=false;
            }
         }
      }
      return $empty && !$root && rmdir($path);
   }
   
   //function to draw 1 file on the page
   function drawFile($f, $ts, $sel) {
      $fType = substr($f,-12, 1);
      $rFile = dataFilename($f);
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
      if ($fsz > 0) echo "$fsz Kb $lapseCount"; else echo 'Busy';
      echo "<br>$fDate<br>$fTime<br>";
      if ($fsz > 0) echo "<a title='$rFile' href='preview.php?preview=$f'>";
      echo "<img src='" . MEDIA_PATH . "/$f' style='width:" . $ts . "px'/>";
      if ($fsz > 0) echo "</a>";
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
            echo "<h1>" . TXT_PREVIEW . ":  " . substr($tFile,-12,5);
            echo "&nbsp;&nbsp;<button class='btn btn-danger' type='submit' name='download1' value='$tFile'>" . BTN_DOWNLOAD . "</button>";
            echo "&nbsp;<button class='btn btn-primary' type='submit' name='delete1' value='$tFile'>" . BTN_DELETE . "</button>";
            if(substr($tFile, -12, 1) == "t") {
               echo "&nbsp;<button class='btn btn-primary' type='submit' name='convert' value='$tFile'>" . BTN_CONVERT . "</button>";
            }
            echo "</p></h1>";
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
