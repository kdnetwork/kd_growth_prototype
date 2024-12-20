<?php

if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}
 global $m;
 $now = time();
 $result = '';
 $id = option::get('kd_growth_offset');
 $max = $m->fetch_array($m->query("SELECT max(id) AS `c` FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth`")); //获取ID最大值
 if ($id < $max['c']) {
     $b = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `id` > {$id} ORDER BY `id` ASC"));
     $p = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `id` = '{$b['pid']}'"));  //获取bduss信息
     $td = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
     $ad = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
     $rc = $m->fetch_array($m->query("SELECT count(id) AS `c` FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `date` > {$td} AND `date` < {$ad} AND `id` ={$b['id']}"));
     if ($rc['c'] < 1) {
        $status = (int)option::uget('kd_growth_sign_only', $b['uid']);
        $tasks = [];
        if ($status == 1) {
            $tasks = getUserGrowthTasks($b['pid']);
        } else {
            $tasks[] = [
                "name" => "每日签到",
                "act_type" => "page_sign",
                "status" => 1,
                "sort_status" => 1,
                "expire_time" => 0
            ];
        }
        $result = [];
        foreach($tasks as $task) {
            if ($task['sort_status'] === -1) {
                continue;
            } elseif ($task['sort_status'] === 2) {
                $result[] = [
                    "name" => $task['name'],
                    "act_type" => $task['act_type'],
                    "status" => 1,
                    "msg" => "success"
                ];
            } elseif ($task['sort_status'] === 1 && ($task['expire_time'] === 0 || $task['expire_time'] > time())) {
                $task_result = json_decode(postUserGrowthRequest($b['pid'], $task['act_type']), true);
                if (($task_result['no']??-1) === 0) {
                    $result[] = [
                        "name" => $task['name'],
                        "act_type" => $task['act_type'],
                        "status" => 1,
                        "msg" => "success"
                    ];
                } else {
                    $result[] = [
                        "name" => $task['name'],
                        "act_type" => $task['act_type'],
                        "status" => 0,
                        "msg" => $task_result['error']
                    ];
                }
            }
        }

        $json_result = json_encode($result, JSON_UNESCAPED_UNICODE);
        $tmpLog = "";
        foreach ($result as $i => $r) {
            if ($i > 0) {
                $tmpLog .= ",";
            }
            $tmpLog .= $r["act_type"] . ":" . $r["status"];
        }
        $log = '<br/>' . date('Y-m-d') . ': ' . $tmpLog . $b['log'];
        //exit();
        $m->query("UPDATE `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` SET `status` = '{$json_result}', `log` = '{$log}',`date` = {$now} WHERE `id` = {$b['id']}");
     }
     option::set('kd_growth_offset', $b['id']);
 } else {
     option::set('kd_growth_offset', 0);
 }
 
 
//清理所有已经解除绑定用户设置的信息
$q = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth`");
while ($x = $m->fetch_array($q)) {
    $b = $m->fetch_array($m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `id` = {$x['pid']}"));
    if (empty($b['id'])) {
        $m->query("DELETE FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `id` = {$x['id']}");
    }
}
