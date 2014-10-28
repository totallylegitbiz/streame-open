<?

/**
 * Console is used to interact with the console..duh
 *
 */


Class Console {

    //Foreground Colors
    const CF_BLACK =      30;
    const CF_RED =        31;
    const CF_GREEN =      32;
    const CF_YELLOW =     33;
    const CF_BLUE =       34;
    const CF_MAGENTA =    35;
    const CF_CYAN =       36;
    const CF_GREY =       37;

    //Background Colors
    const CB_BLACK =      40;
    const CB_RED =        41;
    const CB_GREEN =      42;
    const CB_YELLOW =     43;
    const CB_BLUE =       44;
    const CB_MAGENTA =    45;
    const CB_CYAN =       46;
    const CB_WHITE =      47;

    //Adjusters
    const CA_RESET =      0;
    const CA_BRIGHT =     1;
    const CA_DIM =        2;
    const CA_UNDERLINE =  4;
    const CA_BLINK =      4;
    const CA_REVERSE =    7;
    const CA_HIDDEN =     6;

    /**
     * Echos a line and dies
     *
     * @param var $text
     */

   static function diedie ( $text ) {
      echo self::encode ( $text, self::CF_BLACK, self::CB_RED);
      exit;
    }

    /**
     * Encode a piece of text for output on a console
     *
     * @param var $text
     * @param forground color $fg
     * @param background color $bg
     * @param modifier $attrib
     * @return the encoded text
     */

   static function encode ( $text, $fg, $bg = null, $attrib = 0) {

        if (is_array($text))
            $text = print_r($text,true);

        if ($bg == null)
            return "\033[${attrib};${fg}m${text}\033[0m";
          else
            return "\033[${attrib};${fg};${bg}m${text}\033[0m";
    }

    /**
     * Echos and encodes
     *
     * @param unknown_type $text
     * @param unknown_type $fg
     * @param unknown_type $bg
     * @param unknown_type $attrib
     */

   static function output ( $text, $fg = self::CF_YELLOW, $bg = null, $attrib = 0) {

         echo self::encode ( $text, $fg, $bg = null, $attrib = 0);

    }

    /**
     * Check if currently running on a console, if so optionally die.
     *
     * @param unknown_type $do_die
     * @return unknown
     */

   static function check ($do_die = false) {
             if (!isset($_SERVER["HTTP_HOST"]))
                return true;
             else
               if ($do_die)
                  die("This can only run in the console");
                else
                  return false;

    }

   static function makePid ($pid_file) {
      //Tries to make pid, if exists, return false
      if (file_exists($pid_file))
        if ($old_pid = file_get_contents($pid_file))
         if (self::IsPidActive($old_pid))
            return false;

      $h_pid = getmypid();

      file_put_contents($pid_file, $h_pid);

      return true;
    }

   static function isPidActive ( $pid ) {

      //checks is a pid is still running.
      $cmd = "ps --no-heading -p " . escapeshellarg($pid) . " | wc -l";
      $result = shell_exec($cmd);

      if ($result > 0)
         return true;
       else
         return false;
    }
}
