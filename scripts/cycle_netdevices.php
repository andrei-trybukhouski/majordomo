<?php

define('DEBUG', 1);
define('NUMPING', 4);
chdir(dirname(__FILE__) . '/../');

include_once "./config.php";
include_once "./lib/loader.php";
include_once "./lib/threads.php";

set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once "./load_settings.php";
include_once DIR_MODULES . "control_modules/control_modules.class.php";

$ctl = new control_modules();
$checked_time = 0;

echo date("H:i:s") . " running " . basename(__FILE__) . "\n";

function nd_onlineChanded($ot, $new)
{
    $user = gg("{$ot}.userOwner");
    $hAlert = gg("{$ot}.hangAlert");
    $ih = gg("{$ot}.inHomeindicator");
    $athomeold = gg("{$user}.atHome");
    sg($ot . '.online', $new);
    if ($new == 1) {
        if ($user && $ih) {
 //          if (!$athomeold) sg("{$user}.atHome", 1); 
             cm("{$user}.deviceFound", ['dev' => $ot]);
        }
        sg("{$ot}.onlineAt", time());
        sg("{$ot}.onlineAtTime", date("d.m.Y H:i"));
//        if ($ih == 1) { cm("NobodyHomeMode.deactivate", ['sensor' => $ot]); }
        if ($hAlert) {say("$ot связь восстановлена", 1);}
    } else {
        if ($user && $ih) {
            cm("{$user}.deviceLost", ['dev' => $ot]);
        }
        sg("{$ot}.offlineAt", time());
        sg("{$ot}.offlineAtTime", date("d.m.Y H:i"));
        if ($hAlert) {say("Потеряна связь с $ot", 1);}
    }
    
}

while (1) {
    if (time() - $alive_time >= 15) {
        setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
        $alive_time = time();
    }
    if (time() - $checked_time >= 60) {
        $nds = getObjectsByClass("netDevices"); //[ID] => 87 [TITLE] => homepc
        foreach ($nds as $nd) {
            $ot = $nd['TITLE'];
            $ip = gg($ot . '.ip');
            $port = gg($ot . '.port');
            $oldOnline = gg($ot . '.online');
            if (!$port) {
                $online = myPing($ip,1);
                if ($online == 0 ) {$online = myPing($ip,NUMPING);}
                if ($online == 1 && !$oldOnline) {nd_onlineChanded($ot, 1);}
                if ($online == 0 && $oldOnline) {nd_onlineChanded($ot, 0);}
            } else {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                $s = socket_connect($socket, $ip,    $port);
                socket_close($socket);
                sg($ot . '.online', $s);
            }
            if (DEBUG) {
                print(date("H:i:s") . ' TMR_netDevices: ' . $ot . " $oldOnline -> $online") . "\n";
            }
        }
        $checked_time = time();
    }

    sleep(10);
    if (DEBUG) { print("sleep \n"); }

    if (file_exists('./reboot') || isset($_GET['onetime'])) {
        $db->Disconnect();
        exit;
    }
}

DebMes("Unexpected close of cycle: " . basename(__FILE__));
