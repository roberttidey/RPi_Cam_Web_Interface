<?php
   define('CONFIG_FILE', '/var/www/uconfig');
   $configChanged = false;
   $config = array();
   $logFile = 'pipelog.txt';

   function writeLog($msg) {
      global $logFile;
      $log = fopen($logFile, "a");
      $time = date('[Y/M/d H:i:s]');
      fwrite($log, "$time $msg" . PHP_EOL);
      fclose($log);
   }
   
   function loadConfig() {
      global $config;
      if (file_exists(CONFIG_FILE)) {
         $lines = array();
         $data = file_get_contents(CONFIG_FILE);
         $lines = explode("\n", $data);
         foreach($lines as $line) {
            if (strlen($line) && substr($line, 0, 1) != '#') {
               $index = strpos($line, ' ');
               if ($index !== false) {
                  $key = substr($line, 0, $index);
                  $value = trim(substr($line, $index +1));
                  $config[$key] = $value;
               }
            }
         }
      } 
   }
  
   function addValue($key, $value) {
      global $configChanged, $config;
      if ($config[$key] != $value) {
         $config[$key] = $value;
         $configChanged = true;
      }
   }
   
   function editConfig($cmd) {
      global $config;
      $fatr = array('false', 'true');
      $key = substr($cmd, 0, 2);
      $value = substr($cmd, 3);
      switch($key) {
         case 'px':
            addValue('video_width', substr($value,0,4));
            addValue('video_height', substr($value,5,4));
            addValue('video_fps', substr($value,10,2));
            addValue('MP4Box_fps', substr($value,13,2));
            addValue('image_width', substr($value,16,4));
            addValue('image_height', substr($value,21,4));
            break;
         case 'an':
            addValue('annotation', $value);
            break;
         case 'ab':
            addValue('anno_background', $fatr[$value]);
            break;
         case 'sh':
            addValue('sharpness', $value);
            break;
         case 'co':
            addValue('contrast', $value);
            break;
         case 'br':
            addValue('brightness', $value);
            break;
         case 'sa':
            addValue('saturation', $value);
            break;
         case 'is':
            addValue('iso', $value);
            break;
         case 'vs':
            addValue('video_stabilisation', $fatr[$value]);
            break;
         case 'rl':
            addValue('raw_layer', $fatr[$value]);
            break;
         case 'ec':
            addValue('exposure_compensation', $value);
            break;
         case 'em':
            addValue('exposure_mode', $value);
            break;
         case 'wb':
            addValue('white_balance', $value);
            break;
         case 'mm':
            addValue('metering_mode', $value);
            break;
         case 'ie':
            addValue('image_effect', $value);
            break;
         case 'ce':
            addValue('colour_effect_en', $fatr[substr($value,0,1)]);
            addValue('colour_effect_u', substr($value,2,3));
            addValue('colour_effect_v', substr($value,6,3));
            break;
         case 'ro':
            addValue('rotation', $value);
            break;
         case 'fl':
            addValue('hflip', $fatr[$value & 1]);
            addValue('vflip', $fatr[($value >> 1) & 1]);
            break;
         case 'ri':
            addValue('sensor_region_x', substr($value,0,5));
            addValue('sensor_region_y', substr($value,6,5));
            addValue('sensor_region_w', substr($value,12,5));
            addValue('sensor_region_h', substr($value,18,5));
            break;
         case 'ss':
            addValue('shutter_speed', $value);
            break;
         case 'qu':
            addValue('image_quality', $value);
            break;
         case 'bi':
            addValue('video_bitrate', $value);
            break;
      }
   }
   
   function saveConfig() {
      global $config;
      $cstring= "";
      foreach($config as $key => $value) {
         $cstring .= $key . ' ' . $value . "\n";
      }
      if (cstring != "") {
         $fp = fopen(CONFIG_FILE, 'w');
         fwrite($fp, "#User config file\n");
         fwrite($fp, $cstring);
         fclose($fp);
      }
   }
  
   $pipe = fopen("FIFO","w");
   fwrite($pipe, $_GET["cmd"]);
   fclose($pipe);
   loadConfig();
   editConfig($_GET["cmd"]);
   if ($config && $configChanged) {
      saveConfig();
   }

?>
