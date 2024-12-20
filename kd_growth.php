<?php
global $m;
if (!defined('SYSTEM_ROOT')) { die('Insufficient Permissions'); }

function kd_growth_navi(){
    ?>
    <li <?php if(isset($_GET['plugin']) && $_GET['plugin'] == 'kd_growth') { echo 'class="active"'; } ?>><a href="index.php?plugin=kd_growth"><span class="glyphicon glyphicon glyphicon-check"></span> 用户成长任务</a></li>
    <?php
}

addAction('navi_1','kd_growth_navi');
addAction('navi_7','kd_growth_navi');

function postUserGrowthRequest ($pid, $act_type = "page_sign") {
    $bduss = misc::getCookie($pid);
    $body = [
        "act_type" => $act_type,
        "cuid" => "-",
        "tbs" => misc::getTbs(0, $bduss),
    ];
    $ch = new wcurl('https://tieba.baidu.com/mo/q/usergrowth/commitUGTaskInfo');
    $ch->addcookie("BDUSS=" . $bduss);
    $ch->set(CURLOPT_RETURNTRANSFER, true);
    $data = $ch->post($body);
    return $data;
}

function getUserGrowthTasks ($pid) {
    // all tasks
    $active_tasks = ['daily_task', 'live_task'];
    $bduss = misc::getCookie($pid);

    $ch = new wcurl('https://tieba.baidu.com/mo/q/usergrowth/showUserGrowth');
    $ch->addcookie("BDUSS=" . $bduss);
    $x = json_decode($ch->exec(), true);

    $tasks = [];
    foreach($x['data']['tab_list'] as $task_type_list_list) {
        if ($task_type_list_list['tab_name'] === 'basic') {
            foreach ($task_type_list_list['task_type_list'] as $task_type_list) {
                if (in_array($task_type_list['task_type'], $active_tasks)) {
                    foreach($task_type_list['task_list'] as $task) {
                        $tasks[] = $task;
                    }
                }
            }
        }
    }
    return $tasks;
}
