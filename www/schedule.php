<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');
   //ini_set('display_errors', 'On');
   //error_reporting(E_ALL & ~E_NOTICE);
   //Text labels here
   define('BTN_START', 'Start');
   define('BTN_STOP', 'Stop');
   define('BTN_SAVE', 'Save Settings');
   define('BTN_BACKUP', 'Backup');
   define('BTN_RESTORE', 'Restore');
   define('BTN_SHOWLOG', 'Show Log');
   define('BTN_CLEARLOG', 'Clear Log');
   define('LBL_PERIODS', 'Type;Night;Dawn;Day;Dusk');
   define('LBL_PARAMETERS', 'Parameter;Value');
   define('LBL_DAWN', 'Dawn');
   define('LBL_DAY', 'Day');
   define('LBL_DUSK', 'Dusk');

   define('SCHEDULE_CONFIG', 'schedule.json');
   define('SCHEDULE_CONFIGBACKUP', 'scheduleBackup.json');
 
   define('SCHEDULE_ZENITH', '90.8');
 
   define('SCHEDULE_LOGFILE', 'scheduleLog');
   define('SCHEDULE_FIFOIN', 'Fifo_In');
   define('SCHEDULE_FIFOOUT', 'Fifo_Out');
   define('SCHEDULE_CMDPOLL', 'Cmd_Poll');
   define('SCHEDULE_MODEPOLL', 'Mode_Poll');
   define('SCHEDULE_MAXCAPTURE', 'Max_Capture');
   define('SCHEDULE_LATITUDE', 'Latitude');
   define('SCHEDULE_LONGTITUDE', 'Longtitude');
   define('SCHEDULE_GMTOFFSET', 'GMTOffset');
   define('SCHEDULE_DAWNSTARTMINUTES', 'DawnStart_Minutes');
   define('SCHEDULE_DAYSTARTMINUTES', 'DayStart_Minutes');
   define('SCHEDULE_DAYENDMINUTES', 'DayEnd_Minutes');
   define('SCHEDULE_DUSKENDMINUTES', 'DuskEnd_Minutes');
   define('SCHEDULE_COMMANDSON', 'Commands_On');
   define('SCHEDULE_COMMANDSOFF', 'Commands_Off');
   define('SCHEDULE_MODES', 'Modes');
   
   $debugString = "";
   $schedulePars = array();
   $schedulePars = loadPars(SCHEDULE_CONFIG);
   
   $cliCall = isCli();
   $logFile = $schedulePars[SCHEDULE_LOGFILE];
   $showLog = false;
   $schedulePID = getSchedulePID();
   if (!$cliCall) {
   //Process any POST data
      switch($_POST['action']) {
         case 'start':
            startSchedule();
            $schedulePID = getSchedulePID();
            break;
         case 'stop':
            stopSchedule($schedulePID);
            $schedulePID = getSchedulePID();
            break;
         case 'save':
            writeLog('Saved schedule settings');
            $fp = fopen(SCHEDULE_CONFIG, 'w');
            $saveData = $_POST;
            unset($saveData['action']);
            fwrite($fp, json_encode($saveData));
            fclose($fp);
            $schedulePars = loadPars(SCHEDULE_CONFIG);
            break;
         case 'backup':
            writeLog('Backed up schedule settings');
            $fp = fopen(SCHEDULE_CONFIGBACKUP, 'w');
            fwrite($fp, json_encode($schedulePars));
            fclose($fp);
            break;
         case 'restore':
            writeLog('Restored up schedule settings');
            $schedulePars = loadPars(SCHEDULE_CONFIGBACKUP);
            break;
         case 'showlog':
            $showLog = true;
            break;
         case 'clearlog':
            if (file_exists($logFile)) {
               unlink($logFile);
            }
            break;
      }
   }
   
   function isCli() {
       if( defined('STDIN') ) {
           return true;
       }
       if( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
           return true;
       } 
       return false;
   }
   
   function getSchedulePID() {
      $pids = array();
      exec("pgrep -f -l schedule.php", $pids);
      $pidId = 0;
      foreach($pids as $pid) {
         if (strpos($pid, 'php schedule.php') !== false) {
            $pidId = strpos($pid, ' ');
            $pidId = substr($pid, 0, $pidId);
            break;
         }
      }
      return $pidId;
   }
   
   function startSchedule() {
      $ret = exec("php schedule.php >/dev/null &");
   }

   function stopSchedule($pid) {
      exec("kill $pid");
   }
   function loadPars($config) {
      $pars = initPars();
      if (file_exists($config)) {
         try {
            //get pars from config file and update only values which exist in initPars
            $input = json_decode(file_get_contents($config), true);
            foreach($pars as $key => $value) {
               if (array_key_exists($key, $input)) {
                  $pars[$key] = $input[$key];
               }
            }
         } catch (Exception $e) {
         }
      }
      return $pars;
   }

   function initPars() {
      $pars = array(
         SCHEDULE_LOGFILE => 'scheduleLog.txt',
         SCHEDULE_FIFOIN => '/var/www/FIFO1',
         SCHEDULE_FIFOOUT => '/var/www/FIFO',
         SCHEDULE_CMDPOLL => '0.03',
         SCHEDULE_MODEPOLL => '10',
         SCHEDULE_MAXCAPTURE => '30',
         SCHEDULE_LATITUDE => '52.00',
         SCHEDULE_LONGTITUDE => '0.00',
         SCHEDULE_GMTOFFSET => '0',
         SCHEDULE_DAWNSTARTMINUTES => '-180',
         SCHEDULE_DAYSTARTMINUTES => '0',
         SCHEDULE_DAYENDMINUTES => '0',
         SCHEDULE_DUSKENDMINUTES => '180',
         SCHEDULE_COMMANDSON => array("","tl 20","ca 1","tl 20"),
         SCHEDULE_COMMANDSOFF => array("","tl 0","ca 0","tl 0"),
         SCHEDULE_MODES => array("md 0;em night","md 0;em night","md 0;em auto;md 1","md 0;em night")
      );
      return $pars;
   }

   //Support functions for HTML
   function showScheduleSettings($pars) {
      $headings = explode(';', LBL_PARAMETERS);
      echo '<table class="table-bordered">';
      echo '<tr>';
      foreach($headings as $heading) {
         echo '<th>' . $heading . '</th>';
      }
      echo '</tr>';
      foreach ($pars as $mKey => $mValue) {
         if (!is_array($mValue)) {
            echo "<tr><td>$mKey&nbsp;&nbsp;</td><td><input type='text' autocomplete='off' size='30' name='$mKey' value='" . htmlspecialchars($mValue, ENT_QUOTES) . "'/></td></tr>";
         }
      }
      echo '</table><br><br>';
      echo '<table class="table-bordered">';
      echo '<tr>';
      echo 'Time Offset: ' . getTimeOffset() . '  Sunrise: ' . getSunrise(SUNFUNCS_RET_STRING) . '  Sunset: ' . getSunset(SUNFUNCS_RET_STRING) . '<br>';
      $headings = explode(';', LBL_PERIODS);
      $h = -1;
      $d = dayPeriod();
      foreach($headings as $heading) {
         if ($h != $d) {
            echo '<th>' . $heading . '</th>';
         } else {
            echo '<th style = "background-color: LightGreen;">' . $heading . '</th>';
         }
         $h++;
      }
      echo '</tr>';
      foreach ($pars as $mKey => $mValues) {
         if (is_array($mValues)) {
            echo "<tr><td>$mKey&nbsp;&nbsp;</td>";
            foreach ($mValues as $mValue) {
               echo "<td><input type='text' autocomplete='off' size='24' name='" . $mKey . "[]' value='" . htmlspecialchars($mValue, ENT_QUOTES) . "'/></td>";
            }
            echo '</tr>';
         }
      }
      echo '</table>';
   }

   function displayLog() {
      global $logFile;
      if (file_exists($logFile)) {
         $logData = file_get_contents($logFile);
         echo str_replace(PHP_EOL, '<BR>', $logData);
      } else {
         echo "No log data found";
      }
   }

   function mainHTML() {
      global $schedulePID, $schedulePars, $debugString, $showLog;
      echo '<!DOCTYPE html>';
      echo '<html>';
         echo '<head>';
            echo '<meta name="viewport" content="width=550, initial-scale=1">';
            echo '<title>RPi Cam Download</title>';
            echo '<link rel="stylesheet" href="css/style_minified.css" />';
            echo '<link rel="stylesheet" href="css/preview.css" />';
         echo '</head>';
         echo '<body>';
            echo '<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">';
               echo '<div class="container">';
                  echo '<div class="navbar-header">';
                     if ($showLog) {
                        echo '<a class="navbar-brand" href="schedule.php">';
                     } else {
                        echo '<a class="navbar-brand" href="index.php">';
                     }
                     echo '<span class="glyphicon glyphicon-chevron-left"></span>Back - ' . CAM_STRING . '</a>';
                  echo '</div>';
               echo '</div>';
            echo '</div>';
          
            echo '<div class="container-fluid">';
               echo '<form action="schedule.php" method="POST">';
                  if ($debugString) echo $debugString . "<br>";
                  if ($showLog) {
                     echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='clearlog'>" . BTN_CLEARLOG . "</button><br><br>";
                     displayLog();
                  } else {
                     if ($schedulePID != 0) {
                        echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='stop'>" . BTN_STOP . "</button>";
                     } else {
                        echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='start'>" . BTN_START . "</button>";
                     }
                     echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='save'>" . BTN_SAVE . "</button>";
                     echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='backup'>" . BTN_BACKUP . "</button>";
                     echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='restore'>" . BTN_RESTORE . "</button>";
                     echo "&nbsp&nbsp;<button class='btn btn-primary' type='submit' name='action' value='showlog'>" . BTN_SHOWLOG . "</button><br><br>";
                     showScheduleSettings($schedulePars);
                  }
               echo '</form>';
            echo '</div>';
         echo '</body>';
      echo '</html>';
   }
   
   //Support functions for CLI
   function writeLog($msg) {
      global $logFile;
      $log = fopen($logFile, "a");
      $time = date('[Y/M/d H:i:s]');
      fwrite($log, "$time $msg" . PHP_EOL);
      fclose($log);
   }
   
   function sendCmds($cmdString) {
      global $schedulePars;

      $cmds = explode(';', $cmdString);
      foreach ($cmds as $cmd) {
         if ($cmd != "") {
            writeLog("Send $cmd");
            $fifo = fopen($schedulePars[SCHEDULE_FIFOOUT], "w");
            fwrite($fifo, $cmd);
            fclose($fifo);
            sleep(2);
         }
      }
   }
   
   function getTimeOffset() {
      global $schedulePars;
      if (is_numeric($schedulePars[SCHEDULE_GMTOFFSET])) {
         $offset = $schedulePars[SCHEDULE_GMTOFFSET];
      } else {
         date_default_timezone_set($schedulePars[SCHEDULE_GMTOFFSET]);
         $offset = date_offset_get(new DateTime("now")) / 3600; 
      }
      return $offset;
   }
   
   function getSunrise($format) {
      global $schedulePars;
      return date_sunrise(time(), $format, $schedulePars[SCHEDULE_LATITUDE], $schedulePars[SCHEDULE_LONGTITUDE], SCHEDULE_ZENITH, getTimeOffset());
   }
   
   function getSunset($format) {
      global $schedulePars; 
      return date_sunset(time(), $format, $schedulePars[SCHEDULE_LATITUDE], $schedulePars[SCHEDULE_LONGTITUDE], SCHEDULE_ZENITH, getTimeOffset());
   }

   //Return period of day 0=Night,1=Dawn,2=Day,3=Dusk
   function dayPeriod() {
      global $schedulePars;
      $sr = 60 * getSunrise(SUNFUNCS_RET_DOUBLE);
      $ss = 60 * getSunset(SUNFUNCS_RET_DOUBLE);
      $t = (time() % 86400) / 60;
      if ($t < ($sr + $schedulePars[SCHEDULE_DAWNSTARTMINUTES])) {
         $period = 0;
      } else if ($t < ($sr + $schedulePars[SCHEDULE_DAYSTARTMINUTES])) {
         $period = 1;
      } else if ($t > ($ss + $schedulePars[SCHEDULE_DUSKENDMINUTES])) {
         $period = 0;
      } else if ($t > ($ss + $schedulePars[SCHEDULE_DAYENDMINUTES])) {
         $period = 3;
      } else {
         $period = 2;
      }
      return $period;
   }
   
   function openPipe($pipeName) {
      if (!file_exists($pipeName)) {
         writeLog("Making Pipe to receive capture commands $pipeName");
         posix_mkfifo($pipeName,0666);
         chmod($pipeName, 0666);
      } else {
         writeLog("Capture Pipe already exists $pipeName");
      }
      $pipe = fopen($pipeName,'r+');
      stream_set_blocking($pipe,false);
      return $pipe;
   }
   
   function checkMotion($pipe) {
      try {
         $ret = fread($pipe, 1);
      } catch (Exception $e) {
         $ret = "";
      }
      return $ret;
   }

   function mainCLI() {
      global $schedulePars;
      writeLog("RaspiCam support started");
      $captureCount = 0;
      $pipeIn = openPipe($schedulePars[SCHEDULE_FIFOIN]);
      $lastDayPeriod = -1;
      $lastOnCommand = -1;
      $pollTime = $schedulePars[SCHEDULE_CMDPOLL];
      $modeTime = $schedulePars[SCHEDULE_MODEPOLL];
      $timeCount = $modeTime;
      $timeout = 0;
      $timeoutMax = 0; //Loop test will terminate after this (used in test), set to 0 forever

      while ($timeoutMax == 0 || $timeout < $timeoutMax) {
         usleep($pollTime * 1000000);
         //Check for incoming motion capture requests
         $cmd = checkMotion($pipeIn);
         if ($cmd == '0') {
            if ($lastOnCommand >= 0) {
               writeLog('Stop capture requested');
               $send = $schedulePars[SCHEDULE_COMMANDSOFF][$lastOnCommand];
               if ($send) {
                  sendCmds($send);
                  $lastOnCommand = -1;
               }
            } else {
               writeLog('Stop capture request ignored, already stopped');
               $captureCount = 0;
            }
         } else if ($cmd == '1') {
            if ($lastOnCommand < 0 && $lastDayPeriod >= 0) {
               writeLog('Start capture requested');
               $send = $schedulePars[SCHEDULE_COMMANDSON][$lastDayPeriod];
               if ($send) {
                  sendCmds($send);
                  $lastOnCommand = $lastDayPeriod;
               }
            } else {
               writeLog('Start capture request ignored, already started');
            }
         } else if ($cmd !="") {
            writeLog("Ignore FIFO char $cmd");
         }

         //Action period time change checks at TIME_CHECK intervals
         $timeCount += $pollTime;
         if ($timeCount > $modeTime) {
            $timeCount = 0;
            $timeout += $modeTime;
            if ($lastOnCommand < 0) {
               //No capture in progress, Check if day period changing
               $captureCount = 0;
               $newDayPeriod = dayPeriod();
               if ($newDayPeriod != $lastDayPeriod) {
                  writeLog("New period detected $newDayPeriod");
                  sendCmds($schedulePars[SCHEDULE_MODES][$newDayPeriod]);
                  $lastDayPeriod = $newDayPeriod;
               }
            } else {
               //Capture in progress, Check for maximum
               $captureCount += $modeTime;
               if ($captureCount > $schedulePars[SCHEDULE_MAXCAPTURE]) {
                  writeLog("Maximum Capture reached. Sending off");
                  sendCmds($schedulePars[SCHEDULE_COMMANDSOFF][$lastOnCommand]);
                  $lastOnCommand = -1;
                  $captureCount = 0;
               }
            }
         }
      }    
   }
   
   if (!$cliCall) {
      mainHTML();
   } else {
      mainCLI();
   }
?>
