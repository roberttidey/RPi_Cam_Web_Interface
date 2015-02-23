<!DOCTYPE html>
<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');

   //Text labels here
   define('BTN_SAVE', 'Save Settings');
   define('BTN_SHOWALL', 'Show All');
   define('BTN_SHOWLESS', 'Show Less');
   
   define('MOTION_URL', "http://127.0.0.1:6642/0/");
   
   $filterPars = array("switchfilter","threshold","threshold_tune","noise_level","noise_tune","despeckle","area_detect","mask_file","smart_mask_speed","lightswitch","minimum_motion_frames","width","height","framerate","minimum_frame_time","netcam_url","netcam_userpass","pre_capture","post_capture","gap","target_dir","jpeg_filename","control_port","on_event_start","on_event_end","on_motion_detected","on_area_detected");
      
   $motionConfig = "[pars]\n" . file_get_contents(MOTION_URL . "config/list");
   $motionPars = parse_ini_string($motionConfig, False, INI_SCANNER_RAW);
   $showAll = false;
   $debugString = "";
   
   //Process any POST data
   switch($_POST['action']) {
      case 'save':
         $changed = false;
         foreach($_POST as $key => $value) {
            if (array_key_exists($key, $motionPars)) {
               if ($value != $motionPars[$key]) {
                  setMotionPar($key, $value);
                  $changed = true;
               }
            }
         }
         if ($changed) {
            writeMotionPars();
            $motionConfig = restartMotion();
            $motionPars = parse_ini_string($motionConfig, False, INI_SCANNER_RAW);
         }
         break;
      case 'showAll':
            $showAll = true;
         break;
   }
   
   function setMotionPar($k, $v) {
      global $debugString;
   
      $t = file_get_contents(MOTION_URL . "config/set?" . $k ."=" . urlencode($v)); 
   }
   
   function writeMotionPars() {
      $t = file_get_contents(MOTION_URL . "config/write"); 
   }

   function pauseMotion() {
      $t = file_get_contents(MOTION_URL . "detection/pause");
   }

   function startMotion() {
      $t = file_get_contents(MOTION_URL . "detection/start");
   }

   //restart and fetch updated config list
   function restartMotion() {
      $t = file_get_contents(MOTION_URL . "action/restart");
      $retry = 5;
      do {
         sleep(1);
         $t = file_get_contents(MOTION_URL . "config/list");
         if ($t) {
            return "[pars]\n" . $t;
         }
      } while ($retry > 0);
   }

   function buildParsTable($pars, $fPars, $f) {
      echo "<table>";
      foreach ($pars as $mKey => $mValue) {
         if ($f || in_array($mKey, $fPars)) {
            echo "<tr><td>$mKey</td><td><input type='text' autocomplete='off' size='50' name='$mKey' value='" . htmlspecialchars($mValue, ENT_QUOTES) . "'/></td></tr>";
         }
      }
      echo "</table>";
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
      <form action="motion.php" method="POST">
      <?php
      if ($debugString) echo $debugString . "<br>";
      if ($showAll) {
         echo "<button class='btn btn-primary' type='submit' name='action' value='showLess'>" . BTN_SHOWLESS . "</button>";
      } else {
         echo "<button class='btn btn-primary' type='submit' name='action' value='showAll'>" . BTN_SHOWALL . "</button>";
      }
      echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='save'>" . BTN_SAVE . "</button><br><br>";
      buildParsTable($motionPars, $filterPars, $showAll);
      ?>
      </form>
      </div>
   </body>
</html>
