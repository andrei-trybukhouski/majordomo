<?php
Define('MY_DATE_FMT',"d.m H:i:s");

if (!function_exists('time2Hm')) {
    function time2Hm($tm)
    {
        return date("H:i", $tm);
    }
}

function on4time($ot, $time, $dbg=0) {
    $tout = 5 * 60;
    if ($time > 0) {
        $tout = $time;
    }
    if ($dbg) Debmes("On4time $ot на " . ($tout/60) . " минут");
    cm("{$ot}.turnon");
 //   setTimeOut($ot . "_turnOn4time", "sg('" . $ot . ".status', 0);", $tout);
    setTimeOut("{$ot}_turnOn4time", "cm('{$ot}.turnOff');", $tout);
    return 0;
}


class Astro {
    var   $latitude;
    var   $longitude;

    function Astro() {
//        $this -> latitude = 53.112268;
//        $this -> longitude = 26.014811;
       $this -> latitude = 54.6700;
       $this -> longitude = 25.2607;
    }

    function isDark() {
        if (time() > $this -> sunSetTime())return 1;
        return 0;

    }

    function sunRiseTime() {
        $sun_info = date_sun_info(time(), $this -> latitude, $this -> longitude);
        return $sun_info['sunrise'];

    }

    function sunRiseTime_Hm() {
        return time2Hm($this -> sunRiseTime());
    }

    function sunSetTime() {
        $sun_info = date_sun_info(time(), $this -> latitude, $this -> longitude);
        return $sun_info['sunset'];
    }

    function sunSetTime_Hm() {

        return time2Hm($this -> sunSetTime());
    }

    function dayLength() {
        return  $this -> sunSetTime() - $this -> sunRiseTime();
    }
    function dayLength_Hm() {
        return time2Hm($this -> dayLength());
    }

    function transit() {
        $sun_info = date_sun_info(time(), $this -> latitude, $this -> longitude);
        return $sun_info['transit'];
    }

    function civil_begin() {
        $sun_info = date_sun_info(time(), $this -> latitude, $this -> longitude);
        return $sun_info['civil_twilight_begin'];

    }


    function text() {
        $sun_info = date_sun_info(time(), $this -> latitude, $this -> longitude);
        $astroText = "";

        foreach ($sun_info as $key => $val) {
            if ($key == 'sunrise') {
                $sunRiseTime = $val;
                $astroText = $astroText . 'Восход: ' . date("H:i", $sunRiseTime) . PHP_EOL;
            }

            if ($key == 'sunset') {

                $sunset = $val;
                $day_length = $sunset - $sunRiseTime;

                $astroText = $astroText . 'Заход: ' . date("H:i", $sunset) . PHP_EOL;
                $astroText = $astroText . 'Долгота дня: ' . gmdate("H:i", $day_length) . PHP_EOL;
                $sunSetTime = date("H:i", $sunset);
                $dayLength = gmdate("H:i", $day_length);
            }

            if ($key == 'transit') {
                $astroText = $astroText . 'В зените: ' . date("H:i", $val) . PHP_EOL;
                $transit = date("H:i", $val);
            }

            if ($key == 'civil_twilight_begin') {
                $astroText = $astroText . 'Начало утренних сумерек: ' . date("H:i", $val) . PHP_EOL;
                $civil_begin = date("H:i:s", $val);
            }

            if ($key == 'civil_twilight_end') {
                $astroText = $astroText . 'Конец вечерних сумерек: ' . date("H:i", $val) . PHP_EOL;
            }

        }
        if ($this -> isDark()) {
            $astroText = $astroText . ' Темно ';
        }
        else {
            $astroText = $astroText . ' Светло ';
        }
        return $astroText;
    }
}

//$a = new Astro;
//echo(time2Hm($a->sunSetTime()));
//echo($a->dayLength_Hm());
//echo ($a->sunRiseTime_Hm());



function magicPacket($mac, $addr = '255.255.255.255', $socket_number = 7) {

    //s    plit up the mac address based upon the colons in the string
                $addr_byte = explode(':', $mac);
    $hw_addr = '';

    for ($a = 0; $a < 6; $a++)
              $hw_addr.=chr(hexdec($addr_byte[$a]));
    //c    onvert the hex to its decimal equivalent, encode as a character, and repeat 16 times

    $msg = str_repeat(chr(255), 6);
    //F    F in decimal is 255, which is then encoded as a char as with our mac address
            for ($a = 1; $a <= 16; $a++)
              $msg.= $hw_addr;

    $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    //c    reate our socket

    if ($s == false) {
        echo "Error creating socket!\n";
        echo "Error code is '" . socket_last_error($s) . "'- " . socket_strerror(socket_last_error($s));
        return false;
    }
    else {
        //s        etting a broadcast option to socket:
                //$        opt_ret = socket_set_option($s, 1, 6, TRUE);
        $opt_ret = socket_set_option($s, SOL_SOCKET, SO_BROADCAST, true);
        if ($opt_ret < 0) {
            echo "setsockopt() failed, error: " . strerror($opt_ret) . "\n";
            return false;
        }
        if (socket_sendto($s, $msg, strlen($msg), 0, $addr, $socket_number)) {
            socket_close($s);
            return true;
        }
        else {
            return false;
        }
    }
}

function nowFmt(){
    return date("d.m.Y H:i:s");
}

function getMemStat(){
    $mem_total =  exec("free -m | grep Mem | awk '{print $2}'");
    $mem_usage  = exec("free -m | grep Mem | awk '{print $3}'");
    return "U: $mem_usage / T: $mem_total";

}

function getUserById($user_id){
    $user_rec=SQLSelectOne("SELECT * FROM users WHERE ID=".(int)$user_id);
    $user_title=$user_rec['NAME'];
   // $user_title=preg_replace('/\W/','',$user_title);
    if (!$user_title) {
        $user_title='User'.$user_id;}
    return $user_title;
}

function myPing($host,$c=1){
    $str = exec("ping -c $c $host",$output, $status);
    if ($status == 0) return 1;
    return 0;
}

function mysGWtest($host='192.168.1.206'){
    $socket = socket_create(AF_INET, SOCK_STREAM , SOL_TCP);
    $s = socket_connect( $socket, $host , '5003' );
    socket_close($socket);
    return $s;
}
?>
