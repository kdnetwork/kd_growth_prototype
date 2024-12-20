<?php if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}
loadhead();
global $m;
$uid = UID;
$b = $m->fetch_array($m->query("SELECT count(id) AS `c`FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}"));
if ($b['c'] < 1) {
    echo '<div class="alert alert-warning">您需要先绑定至少一个百度ID才可以使用本功能</div>';
    die;
}
if (isset($_GET['save'])) {
    $sign_only = isset($_POST['c']) ? $_POST['c'] === '0' : '1';
    option::uset('kd_growth_sign_only', $sign_only ? 1 : 0, $uid);
    redirect('index.php?plugin=kd_growth&success=' . urlencode('您的设置已成功保存'));
}
if (isset($_GET['newuser'])) {
    $pid = isset($_POST["pid"]) ? sqladds($_POST['pid']) : '';
    if (!empty($pid)) {
        // pre check
        $list = $m->once_fetch_array("SELECT COUNT(*) as c FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `uid` = {$uid} AND `pid` = {$pid}");
        if ($list['c'] > 0) {
            redirect('index.php?plugin=kd_growth&error=' . urlencode('帐号已存在'));
        } else {
            $m->query("INSERT INTO `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` (`uid`, `pid`) VALUES ({$uid}, {$pid})");
            redirect('index.php?plugin=kd_growth&success=' . urlencode("已添加帐号 {$pid}！"));
        }
    } else {
        redirect('index.php?plugin=kd_growth&error=' . urlencode('PID不合法'));
    }
}
if (isset($_GET['duser'])) {
    $id = isset($_GET['id']) ? sqladds($_GET['id']) : '';
    if (!empty($id)) {
        $m->query("DELETE FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `id` = '{$id}' AND `uid` = {$uid}");
        redirect('index.php?plugin=kd_growth&success=' . urlencode('已成功删除该帐号！'));
    } else {
        redirect('index.php?plugin=kd_growth&error=' . urlencode('ID不合法'));
    }
}
if (isset($_GET['dauser'])) {
    global $m;
    $m->query("DELETE FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `uid` = {$uid}");
    redirect('index.php?plugin=kd_growth&success=' . urlencode('帐号列表已成功清空！'));
}

?>
<h2>成长任务</h2>

<br>
<?php
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>
<h4>基本设置</h4>
<br>
<form action="index.php?plugin=kd_growth&save" method="post">
    <table class="table table-hover">
        <tbody>
        <tr>
            <td>
                <b>仅签到</b><br>
                只做签到任务，关闭后将会尝试完成所有日常任务，默认开启
            </td>
            <td>
                <input type="radio" name="c"
                       value="1" <?php echo empty(option::uget('kd_growth_sign_only', $uid)) ? 'checked' : '' ?>> 仅签到
                <input type="radio" name="c"
                       value="0" <?php echo empty(option::uget('kd_growth_sign_only', $uid)) ? '' : 'checked' ?>> 签全部
            </td>
        </tr>
        <tr>
            <td>
                <input type="submit" class="btn btn-primary" value="保存设置">
            </td>
            <td></td>
        </tr>
        </tbody>
    </table>
</form>
<br>
<h4>帐号列表</h4>
<br>
<div class="bs-example bs-example-tabs" data-example-id="togglable-tabs">
    <?php
    $baiduAccount = [];
    foreach($i["user"]["baidu_portrait"] as $pid => $portrait_) {
        $baiduAccount[$pid] = [
            "pid" => $pid,
            "portrait" => $portrait_,
            "name" => $i["user"]["baidu"][$pid]
        ];
    }
    ?>
    <div id="myTabContent" class="tab-content">
        <?php
        $b = 0;
        $bid = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "baiduid` WHERE `uid` = {$uid}");
        while ($r = $m->fetch_array($bid)) {
            ?>
            <div role="tabpanel" class="tab-pane fade <?= empty($b) ? 'active in' : '' ?>" id="b<?= $r['id'] ?>">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>序号</td>
                        <td>帐号</td>
                        <td>状态</td>
                        <td>上次执行</td>
                        <td>日志</td>
                        <td>操作</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $a = 0;
                    $lr = $m->query("SELECT * FROM `" . DB_NAME . "`.`" . DB_PREFIX . "kd_growth` WHERE `uid` = {$uid} ORDER BY `id`");
                    while ($x = $m->fetch_array($lr)) {
                        $a++;
                        $date = date('Y-m-d H:i:s', $x['date']); ?>
                        <tr>
                            <td><?= $x['id'] ?></td>
                            <td><a href="https://tieba.baidu.com/home/main?id=<?= $baiduAccount[$x['pid']]['portrait'] ?>"
                                   target="_blank"><?= $baiduAccount[$x['pid']]['name']??$baiduAccount[$x['pid']]['portrait'] ?></a></td>
                            <td><?php 
                            if (!$x['status']) {
                                echo "暂无记录";
                            } else {
                                foreach(json_decode($x['status'], true) as $value) {echo "{$value['name']}: " . ($value['status'] ? '✅' : '❌') . "<br />";}
                            }?></td>
                            <td><?= $date ?></td>
                            <td>
                                <a class="btn btn-info" href="javascript:;" data-toggle="modal"
                                   data-target="#LogUser<?= $x['id'] ?>">查看</a>
                            </td>
                            <td>
                                <a class="btn btn-danger" href="javascript:;" data-toggle="modal"
                                   data-target="#DelUser<?= $x['id'] ?>">删除</a>
                            </td>
                        </tr>
                        <div class="modal fade" id="LogUser<?= $x['id'] ?>" tabindex="-1" role="dialog"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span
                                                aria-hidden="true">&times;</span><span
                                                class="sr-only">Close</span></button>
                                        <h4 class="modal-title">日志详情</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="input-group" style="word-break: break-word;">
                                                    <?= empty($x['log']) ? '暂无日志' : $x['log'] ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div><!-- /.modal -->

                        <div class="modal fade" id="DelUser<?= $x['id'] ?>" tabindex="-1" role="dialog"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span
                                                aria-hidden="true">&times;</span><span
                                                class="sr-only">Close</span></button>
                                        <h4 class="modal-title">温馨提示</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form action="index.php?plugin=kd_growth&duser&id=<?= $x['id'] ?>"
                                              method="post">
                                            <div class="input-group">
                                                您确定要删除这个用户吗(删除后无法恢复)？
                                            </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                                        <button type="submit" class="btn btn-primary">确定</button>
                                    </div>
                                    </form>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div><!-- /.modal -->
                                <?php
                    }
                    if (empty($a)) {
                        echo "<tr><td>暂无记录</td><td></td><td></td><td></td><td></td><td></td></tr>";
                    } ?>
                    </tbody>
                </table>
            </div>
            <?php
            $b++;
        }
        ?>
    </div>
</div>

<a class="btn btn-success" href="javascript:;" data-toggle="modal" data-target="#AddUser">添加帐号</a>
<a class="btn btn-danger" href="javascript:;" data-toggle="modal" data-target="#DelUser">清空列表</a>

<div class="modal fade" id="AddUser" tabindex="-1" role="dialog" aria-labelledby="AddUser" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?plugin=kd_growth&newuser" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                            aria-hidden="true">&times;</span><span
                            class="sr-only">Close</span></button>
                    <h4 class="modal-title">选择帐号</h4>
                </div>
                <div class="modal-body">
                    <div class="input-group">
                        <span class="input-group-addon">请选择账号</span>
                        <select name="pid" required="" class="form-control">
                            <?php
                            foreach ($baiduAccount as $pid => $value) {
                                echo '<option value="' . $pid . '">' . $value['name']??$value['portrait'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="DelUser" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?plugin=kd_growth&dauser" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span
                            aria-hidden="true">&times;</span><span
                            class="sr-only">Close</span></button>
                    <h4 class="modal-title">温馨提示</h4>
                </div>
                <div class="modal-body">
                    
                    <div class="input-group">
                        您确定要清空列表（该执行后无法恢复）？
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确定</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->