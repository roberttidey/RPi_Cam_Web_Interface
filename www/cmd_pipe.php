<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');

   $configChanged = false;
   $config = array();
   $dfltConfig = array();
 
   function addValue($key, $value) {
      global $configChanged, $config, $dfltConfig;
      if ($dfltConfig[$key] == $value) {
       unset($config[$key]);
       $configChanged = true;
      } else {
         if ($config[$key] != $value) {
            $config[$key] = $value;
            $configChanged = true;
         }
      }
   }
   
   function editConfig($cmd) {
      global $config;
      $fatr = array('false', 'true');
      $key = substr($cmd, 0, 2);
      $value = substr($cmd, 3);
      $values = explode(" ", $value);
      switch($key) {
         case 'px':
            addValue('video_width', $values[0]);
            addValue('video_height', $values[1]);
            addValue('video_fps', $values[2]);
            addValue('MP4Box_fps', $values[3]);
            addValue('image_width', $values[4]);
            addValue('image_height', $values[5]);
            break;
         case 'an':
            addValue('annotation', $value);
            break;
         case 'ab':
            addValue('anno_background', $fatr[$value]);
            break;
         case 'av':
            addValue('anno_version', $value);
            break;
         case 'as':
            addValue('anno_text_size', $value);
            break;
         case 'at':
            addValue('anno3_custom_text_colour',$values[0]);
            addValue('anno3_custom_text_Y',$values[1]);
            addValue('anno3_custom_text_U',$values[2]);
            addValue('anno3_custom_text_V',$values[3]);
            break;
         case 'ac':
            addValue('anno3_custom_background_colour',$values[0]);
            addValue('anno3_custom_background_Y',$values[1]);
            addValue('anno3_custom_background_U',$values[2]);
            addValue('anno3_custom_background_V',$values[3]);
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
            addValue('colour_effect_en', $fatr[$values[0]]);
            addValue('colour_effect_u', $values[1]);
            addValue('colour_effect_v', $values[2]);
            break;
         case 'ro':
            addValue('rotation', $value);
            break;
         case 'fl':
            addValue('hflip', $fatr[$value & 1]);
            addValue('vflip', $fatr[($value >> 1) & 1]);
            break;
         case 'ri':
            addValue('sensor_region_x', $values[0]);
            addValue('sensor_region_y', $values[1]);
            addValue('sensor_region_w', $values[2]);
            addValue('sensor_region_h', $values[3]);
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
         case 'tv':
            addValue('tl_interval', $values[0]);
            break;
         case 'bo':
            if ($value == '0') $value = 'false';
            else if($value == '2') $value = 'background';
            else $value = 'true';
            addValue('MP4Box', $value);
            break;
      }
   }
   
   $pipe = fopen("FIFO","w");
   fwrite($pipe, $_GET["cmd"]);
   fclose($pipe);
   $dfltConfig = readConfig($dfltConfig, CONFIG_FILE1);
   $config = readConfig($config, CONFIG_FILE2);
   editConfig($_GET["cmd"]);
   if ($config && $configChanged) {
      saveUserConfig($config);
   }

?>
