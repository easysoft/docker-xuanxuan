#!/opt/zbox/bin/php
<?php
array_shift($argv);
$flipArgv = array_flip($argv);
$basePath = dirname(dirname(__FILE__));

if($basePath != '/opt/zbox') die("Run it in path /opt/zbox/\n");
if(empty($argv) or isset($flipArgv['--help']) or isset($flipArgv['-h']))
{
    echo <<<EOD
Usage: zbox.php {start|stop|restart|status}

Options:
    -h --help Show help.

EOD;
    exit;
}

/* Process argv. */
$params = array();
foreach($flipArgv as $key => $val)
{
    if(strpos($key, '-') !== 0) continue;
    if($key == '--aport') $key = '-ap';
    if($key == '--mport') $key = '-mp';
    if(isset($argv[$val + 1]) and is_numeric($argv[$val + 1]))
    {
        $params[$key] = $argv[$val + 1];
        unset($argv[$val]);
        unset($argv[$val + 1]);
    }
}

if(isset($params['-ap'])) changePort($basePath . '/etc/apache/httpd.conf', $params['-ap'], array('^Listen +([0-9]+)', '<VirtualHost +.*:([0-9]+)>'));

if(isset($params['-mp']))
{
    changePort($basePath . '/etc/mysql/my.cnf', $params['-mp'], '^port *= *([0-9]+)');
    changePort($basePath . '/app/htdocs/index.php', $params['-mp'], 'localhost\:([0-9]+)\&');

    $myReg = '^\$config->db->port *= *.([0-9]+)..*;';
    if(file_exists("$basePath/app/xxb/config/my.php"))
    {
        `chmod 777 $basePath/app/xxb/config/my.php`;
        $myFile = "$basePath/app/xxb/config/my.php";
        changePort($myFile, $params['-mp'], $myReg);
    }
}

if(!empty($argv)) $params['-k'] = reset($argv);
if(isset($params['-k']))
{
    if(strpos(file_get_contents('/etc/group'), 'nogroup') === false) echo `groupadd nogroup`;
    if(strpos(file_get_contents('/etc/passwd'), 'nobody') === false) echo `useradd nobody`;
    `chmod -R 777 $basePath/tmp`;
    `chmod -R 777 $basePath/logs`;
    `chmod -R 777 $basePath/app/xxb/tmp`;
    `chmod -R 777 $basePath/app/xxb/www/data`;
    `chmod -R 644 $basePath/etc/mysql/my.cnf`;
    `chown -R nobody $basePath/data/mysql`;

    switch($params['-k'])
    {
    case 'start':
    	$xxd = `ps aux|grep '.\/xxd'|grep -v 'grep'`;
        if($xxd)
        {
            echo "XXD is running\n";
        }
        else
        {
            getXXKey();

        }
        break;
    case 'stop':
    	$xxd = `ps aux|grep '.\/xxd'|grep -v 'grep'`;
        if($xxd)
        {
            chdir("$basePath/run/xxd");                                                                            
            `./xxd -service stop; ./xxd -service uninstall`;
            sleep(2);
            $xxd = `ps aux|grep '.\/xxd'|grep -v 'grep'`;
            echo empty($xxd) ? "Stop xxd success.\n" : "Stop xxd fail\n";
        }
        else
        {
            echo "XXD is not running\n";
        }
        break;
    case 'restart':
    	echo `ps aux|grep './xxd'| awk '{print $2}'|xargs sudo kill -9`;
        getXXKey();
        sleep(2);
        $oldDir = getcwd();                                                                                        
        chdir("$basePath/run/xxd");                                                                                
        `./xxd -service stop; ./xxd -service uninstall; ./xxd -service install; ./xxd -service start`;             
        chdir($oldDir);
        sleep(2);
        $xxd = `ps aux|grep '.\/xxd'|grep -v 'grep'`;
        echo empty($xxd) ? "Restart xxd fail.\n"   : "Restart xxd success\n";
        break;
    case 'status':
        $httpd = `ps aux|grep '\/opt\/zbox\/run\/apache\/httpd '`;
        $mysql = `ps aux|grep '\/opt\/zbox\/run\/mysql\/mariadbd '`;
        $xxd   = `ps aux|grep '\/opt\/zbox\/run\/xxd\/xxd'`;
        echo empty($httpd) ? "Apache is not running\n" : "Apache is running\n";
        echo empty($mysql) ? "Mysql is not running\n" : "Mysql is running\n";
        echo empty($xxd)   ? "XXD is not running\n" : "XXD is running\n";
    }
}

function changePort($file, $port, $regs)
{
    if(!is_array($regs)) $regs = array($regs);
    $lines = file($file);
    foreach($lines as $i => $line)
    {
        foreach($regs as $reg)
        {
            if(preg_match("/$reg/", $line, $matches)) $lines[$i] = str_replace($matches[1], $port, $line);
        }
    }
    file_put_contents($file, join($lines));
}

function getXXKey()
{
    global $basePath;
    $xxKey    = array();
    $xxConfig = '';
    if(file_exists("$basePath/app/xxb/config/my.php"))
    {
        $myFile = "$basePath/app/xxb/config/my.php";
        $dbh    = connectDB(getDbConfig($myFile));
        if($dbh)
        {
            $xxKey     = setXXKey($dbh);
            $key       = $xxKey['key'];
            $xxConfig .= "xxb=http://127.0.0.1/xxb/x.php,{$key}\n";
        }
    }
    if($xxConfig) $xxKey['server'] = $xxConfig;

    if($xxKey)
    {
        if(!file_exists('/opt/zbox/run/xxd/config/xxd.conf'))
        {
            $xxdConf  = str_replace(array('%ip%', '%chatPort%', '%commonPort%', '%https%', '%server%'), 
            array($xxKey['ip'], $xxKey['chatPort'], $xxKey['commonPort'], $xxKey['https'], $xxKey['server']),
            file_get_contents('/opt/zbox/run/xxd/config/xxd.conf.res'));
            file_put_contents('/opt/zbox/run/xxd/config/xxd.conf', $xxdConf);
        }
        else
        {
            $lines     = file('/opt/zbox/run/xxd/config/xxd.conf');
            $xxdConf   = '';
            $newServer = true;
            foreach($lines as $line)
            {
                if(strpos($line, 'x.php') !== false)
                {
                    if($newServer) $xxdConf .= $xxConfig;
                    $newServer = false;
                    continue;
                }
                $xxdConf .= $line;
            }
        }
    }
}

function setXXKey($dbh)
{
    $sn = md5(mt_rand(0, 99999999) . microtime());
    $xxKey['turnon']     = 1;
    $xxKey['key']        = $sn;
    $xxKey['chatPort']   = 11444;
    $xxKey['commonPort'] = 11443;
    $xxKey['ip']         = '0.0.0.0';
    $xxKey['https']      = 'off';

    $rows = $dbh->query("select * from xxb_config where `owner`='system' and `module`='common' and `section`='xuanxuan' and `key`='key'")->fetchAll();
    if(!empty($rows))
    {
        foreach($rows as $row) $xxKey[$row['key']] = $row['value'];
        return $xxKey;
    }

    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='turnon', `value`='1'");
    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='key', `value`='{$sn}'");
    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='chatPort', `value`='11444'");
    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='commonPort', `value`='11443'");
    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='ip', `value`='0.0.0.0'");
    $dbh->exec("REPLACE INTO xxb_config SET `owner`='system', `module`='common', `section`='xuanxuan', `key`='https', `value`='off'");

    return $xxKey;
}

function getDbConfig($configFile)
{
    $files = file($configFile);
    $dbConfig = new stdclass();
    foreach($files as $line)
    {
        if(strpos($line, '//') === 0) continue;
        $line = trim(trim($line), ';');
        if(strpos($line, 'db->host') !== false)     list($tmp, $dbConfig->host)     = explode('=', $line);
        if(strpos($line, 'db->port') !== false)     list($tmp, $dbConfig->port)     = explode('=', $line);
        if(strpos($line, 'db->user') !== false)     list($tmp, $dbConfig->user)     = explode('=', $line);
        if(strpos($line, 'db->password') !== false) list($tmp, $dbConfig->password) = explode('=', $line);
        if(strpos($line, 'db->name') !== false)     list($tmp, $dbConfig->name)     = explode('=', $line);
    }
    $dbConfig->host     = trim(trim(trim($dbConfig->host), "'"), '"');
    $dbConfig->port     = trim(trim(trim($dbConfig->port), "'"), '"');
    $dbConfig->user     = trim(trim(trim($dbConfig->user), "'"), '"');
    $dbConfig->password = trim(trim(trim($dbConfig->password), "'"), '"');
    $dbConfig->name     = trim(trim(trim($dbConfig->name), "'"), '"');

    return $dbConfig;
}

function connectDB($dbConfig)
{
    $dbh = null;
    $dsn = "mysql:host={$dbConfig->host}; port={$dbConfig->port}; dbname={$dbConfig->name}";
    try
    {
        $dbh = new PDO($dsn, $dbConfig->user, $dbConfig->password);
        $dbh->exec("SET NAMES UTF-8");
    }
    catch (PDOException $exception)
    {
    }
    return $dbh;
}
