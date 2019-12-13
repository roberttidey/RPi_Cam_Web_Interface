# Domain Objects

## File (saved pictures or videos)
• filename (string)
• created_at (date | timestamp)
• type (PICTURE | VIDEO)

## System
• memory
  • used (float)
  • max (float)

## Schedule
• fifo_in (string)
• fifo_out (string)
• cmd_poll (float)
• mode_poll (integer)
• management_interval (integer)
• management_command (string?)
• purge_video_hours (integer)
• purge_image_hours (integer)
• purge_lapse_hours (integer)
• gmt_offset (integer)
• purge_space_modeex (OFF | MIN_SPACE% | MAX_USAGE% | MIN_SPACE_GB | MAX_SPACE_GB)
• purge_space_level (integer)
• dawn_start_minutes (integer)
• day_start_minutes (integer)
• day_end_minutes (integer)
• dusk_end_minutes (integer)
• latitude (float)
• longitude (float)
• max_capture (integer)
• day_mode (SUN_BASED | ALL_DAY | FIXED_TIMES)
• auto_capture_interval (integer)
• auto_camera_interval (integer)

## CameraSettings
• resolution
  • video_width (integer)
  • video_height (integer)
  • video_fps_recording (integer)
  • video_fps_boxing (integer)
  • image_width (integer)
  • image_height (integer)
• time_lapse (integer)
• video_split (integer)
• annotation
  • text (string)
  • background (boolean)
  • size (integer)
  • text_color
    • y (integer)
    • u (integer)
    • v (integer)
    • disabled (boolean)
  • background_color
    • y (integer)
    • u (integer)
    • v (integer)
    • disabled (boolean)
  



# Endpoints

## Files
GET /files returns a list of Files

DELETE /files
body {
  fileNames: filename[]
}

