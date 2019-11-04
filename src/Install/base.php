<?php

define('INSTALL_LOCK_FILE', __DIR__.'/../../../../../storage/install.lock');
define('ENV_FILE_EXAMPLE', __DIR__.'/../../../../../.env.example');
define('ENV_FILE', __DIR__.'/../../../../../.env');

include __DIR__.'/../../../laravel-base/src/Support/FileHelper.php';
include __DIR__.'/../../../laravel-base/src/Support/EnvHelper.php';

function randString($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function jsonErr($msg)
{
    header('Content-type: application/json');
    exit(json_encode(array(
        'code' => -1,
        'msg' => $msg
    )));
}


function post($k, $defaultValue = '')
{
    return isset($_POST[$k]) ? $_POST[$k] : $defaultValue;
}

if(!file_exists(ENV_FILE)){
    file_put_contents(ENV_FILE,"APP_ENV=beta
APP_DEBUG=true
APP_KEY=".randString(32));
}

if (!empty($_POST)) {

    if (file_exists(INSTALL_LOCK_FILE)) {
        jsonErr("删除install.lock文件再安装 :(");
    }

    $dbHost = post('db_host');
    $dbDatabase = post('db_database');
    $dbUsername = post('db_username');
    $dbPassword = post('db_password', '');
    $dbPrefix = post('db_prefix', '');
    if (empty($dbHost)) {
        jsonErr("数据库主机名不能为空");
    }
    if (empty($dbDatabase)) {
        jsonErr("数据库数据库不能为空");
    }
    if (empty($dbUsername)) {
        jsonErr("数据库用户不能为空");
    }
    $username = post('username');
    $password = post('password');
    if (empty($username)) {
        jsonErr("管理用户不能为空");
    }
    if (empty($password)) {
        jsonErr("管理用户密码不能为空");
    }

    // 数据库连接检测
    try {
        new PDO("mysql:host=$dbHost;dbname=$dbDatabase", $dbUsername, $dbPassword);
    } catch (\Exception $e) {
        jsonErr('连接数据信息 ' . $dbHost . '.'.$dbDatabase.' 失败!');
    }

    // 替换.env文件
    $envContent = file_get_contents(ENV_FILE_EXAMPLE);

    $envContent = preg_replace("/DB_HOST=(.*?)\\n/", "DB_HOST=" . $dbHost . "\n", $envContent);
    $envContent = preg_replace("/DB_DATABASE=(.*?)\\n/", "DB_DATABASE=" . $dbDatabase . "\n", $envContent);
    $envContent = preg_replace("/DB_USERNAME=(.*?)\\n/", "DB_USERNAME=" . $dbUsername . "\n", $envContent);
    $envContent = preg_replace("/DB_PASSWORD=(.*?)\\n/", "DB_PASSWORD=" . $dbPassword . "\n", $envContent);
    $envContent = preg_replace("/DB_PREFIX=(.*?)\\n/", "DB_PREFIX=" . $dbPrefix . "\n", $envContent);

    $envContent = preg_replace("/APP_KEY=(.*?)\\n/", "APP_KEY=" . randString(32) . "\n", $envContent);
    file_put_contents(ENV_FILE, $envContent);

    // 数据库升级
    $url = 'http://' . $_SERVER['HTTP_HOST'] . '/install/execute?username=' . urlencode($username) . '&password=' . urlencode($password);
    $content = @file_get_contents($url);
    if ('ok' != $content) {
        if (empty($content)) {
            $content = '请求安装链接失败';
        }
        jsonErr($content);
    }

    file_put_contents(INSTALL_LOCK_FILE, 'lock');

    if(function_exists('after_install_callback')){
        after_install_callback();
    }

    header('Content-type: application/json');
    exit(json_encode(array(
        'code' => 0,
        'msg' => '安装成功',
        'redirect' => '/admin/'
    )));
}

?>
<!doctype html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <script src="/assets/init.js"></script>
    <script src="/assets/basic/js/basic.js"></script>
    <link rel="stylesheet" href="/assets/uikit/css/ui.css">
    <title>安装助手</title>
    <style type="text/css">
        body, html {
            min-height: 100%;
        }
    </style>
</head>
<body style="background:#333;padding:40px 0;">

<?php
$error = 0;
function ok($msg)
{
    echo '<div class="uk-alert uk-alert-success"><span class="uk-icon-check"></span> ' . $msg . '</div>';
}

function err($msg,$solutionUrl = null)
{
    global $error;
    $error++;
    echo '<div class="uk-alert uk-alert-danger"><span class="uk-icon-times"></span> ' . $msg . ' '.($solutionUrl?'<a target="_blank" href="'.$solutionUrl.'">解决办法</a>':'').'</div>';
}

function env_writable()
{
    $file = '../../.env';
    if (!file_exists($file)) {
        if (false === file_put_contents($file, "")) {
            @unlink($file);
            return false;
        }
        @unlink($file);
        return true;
    }
    $content = @file_get_contents($file);
    if (false === file_put_contents($file, $content)) {
        return false;
    }
    return true;
}

function rewrite_ok()
{
    if (file_exists('../../storage/rewrite.skip')) {
        return true;
    }
    $url = 'http://' . $_SERVER['HTTP_HOST'] . '/install/ping';
    $content = @file_get_contents($url);
    if ('ok' == $content) {
        return true;
    }
    return false;
}

function env($key, $defaultValue = '')
{
    static $values = null;
    if (null === $values) {
        //$content = file_get_contents('../../.env.example');
		$content=file_get_contents(ENV_FILE_EXAMPLE);
        foreach (explode("\n", $content) as $line) {
            if ($line = trim($line)) {
                list($k, $v) = explode('=', $line);
                $values[trim($k)] = trim($v);
            }
        }
    }
    return isset($values[$key]) ? $values[$key] : $defaultValue;
}

?>

<div style="width:600px;margin:0 auto;">

    <?php if (file_exists('../../storage/install.lock')) { ?>
        <div class="uk-alert uk-alert-danger uk-text-center">系统无需重复安装</div>
    <?php } else { ?>

    <h1 class="uk-text-center" style="color:#FFF;">
        安装助手
    </h1>

    <div class="uk-panel uk-panel-header" style="background:#FFF;">
        <h2 class="uk-panel-title" style="padding:10px;">
            环境检查
        </h2>
        <div class="uk-panel-body">
            <?php ok('系统：' . PHP_OS); ?>
            <?php version_compare(PHP_VERSION, '5.5.9', '>=') ? ok('PHP版本' . PHP_VERSION) : err('PHP版本>=5.5.9 当前为'.PHP_VERSION,'http://bbs.tecmz.com/thread/70'); ?>
            <?php ok('最大上传：' . \Edwin404\Base\Support\FileHelper::formatByte(\Edwin404\Base\Support\EnvHelper::env('uploadMaxSize'))); ?>
            <?php function_exists('openssl_open') ? ok('OpenSSL PHP 扩展') : err('缺少 OpenSSL PHP 扩展'); ?>
            <?php function_exists('exif_read_data') ? ok('Exif PHP 扩展') : err('缺少 Exif PHP 扩展'); ?>
            <?php function_exists('shell_exec') ? ok('shell_exec 函数') : err('缺少 shell_exec 函数','http://bbs.tecmz.com/thread/73'); ?>
            <?php function_exists('proc_open') ? ok('proc_open 函数') : err('缺少 proc_open 函数','http://bbs.tecmz.com/thread/71'); ?>
            <?php function_exists('proc_get_status') ? ok('proc_get_status 函数') : err('缺少 proc_get_status 函数','http://bbs.tecmz.com/thread/72'); ?>
            <?php function_exists('bcmul') ? ok('bcmath 扩展') : err('缺少 PHP bcmath 扩展'); ?>
            <?php class_exists('pdo') ? ok('PDO PHP 扩展') : err('缺少 PDO PHP 扩展'); ?>
            <?php (class_exists('pdo') && in_array('mysql',PDO::getAvailableDrivers())) ? ok('PDO Mysql 驱动正常') : err('缺少 PDO Mysql 驱动'); ?>
            <?php function_exists('mb_internal_encoding') ? ok('缺少 Mbstring PHP 扩展') : err('Mbstring PHP 扩展'); ?>
            <?php function_exists('token_get_all') ? ok('缺少 Tokenizer PHP 扩展') : err('Tokenizer PHP 扩展'); ?>
            <?php function_exists('finfo_file') ? ok('缺少 PHP Fileinfo 扩展') : err('PHP Fileinfo 扩展'); ?>
            <?php is_writable('../../storage/') ? ok('/storage/目录可写') : err('/storage/目录不可写'); ?>
            <?php is_writable('../../public/') ? ok('/public/目录可写') : err('/public/目录不可写'); ?>
            <?php is_writable('../../bootstrap/cache/') ? ok('/bootstrap/cache/目录可写') : err('/bootstrap/cache/目录不可写'); ?>
            <?php rewrite_ok() ? ok('Rewrite规则正确') : err('Rewrite规则错误'); ?>
        </div>
    </div>

    <?php if ($error > 0) { ?>
        <div class="uk-alert uk-alert-danger uk-text-center">请解决以上问题再进行安装</div>
    <?php } else if (!env_writable()) { ?>
        <div class="uk-alert uk-alert-danger uk-text-center">/.env文件不可写，请手动配置安装</div>
    <?php } else { ?>

        <div class="uk-panel uk-panel-header" style="background:#FFF;">
            <div class="uk-panel-body">
                <form class="uk-form uk-form-stacked" method="post" action="?" data-ajax-form>
                    <fieldset>
                        <legend>MySQL数据库信息</legend>
                        <div class="uk-form-row">
                            <label class="uk-form-label">主机</label>
                            <div class="uk-form-controls">
                                <input type="text" name="db_host" value=""/>
                            </div>
                        </div>
                        <div class="uk-form-row">
                            <label class="uk-form-label">数据库名</label>
                            <input type="text" name="db_database" value=""/>
                        </div>
                        <div class="uk-form-row">
                            <label class="uk-form-label">用户名</label>
                            <input type="text" name="db_username" value=""/>
                        </div>
                        <div class="uk-form-row">
                            <label class="uk-form-label">密码</label>
                            <input type="text" name="db_password" value=""/>
                        </div>
                        <div class="uk-form-row">
                            <label class="uk-form-label">数据表前缀</label>
                            <input type="text" name="db_prefix" value=""/>
                        </div>
                    </fieldset>
                    <div style="height:20px;"></div>
                    <fieldset>
                        <legend>管理信息</legend>
                        <div class="uk-form-row">
                            <label class="uk-form-label">用户</label>
                            <input type="text" name="username" value=""/>
                        </div>
                        <div class="uk-form-row">
                            <label class="uk-form-label">密码</label>
                            <input type="text" name="password" placeholder=""/>
                        </div>
                    </fieldset>
                    <div style="height:20px;"></div>
                    <fieldset class="uk-text-center">
                        <button type="submit" class="uk-button uk-button-primary">提交</button>
                    </fieldset>
                </form>
            </div>
        </div>
    <?php } ?>
</div>
<?php } ?>
</div>

</body>
</html>