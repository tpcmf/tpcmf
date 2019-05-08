<?php namespace Phpcmf\Controllers;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


define('IS_INSTALL', 1);

// 安装程序
class Install extends \Phpcmf\Common
{

    public $db;
    private $lock;

    /**
     * 初始化共享控制器
     */
    public function __construct(...$params)
    {
        parent::__construct(...$params);
        $this->lock = WRITEPATH.'install.lock';
        is_file($this->lock) && exit('安装程序已经被锁定，重新安装请删除：cache/install.lock');
        if (version_compare(PHP_VERSION, '7.1.0') < 0) {
            echo "<font color=red>PHP版本必须在7.2以上</font>";exit;
        }
        define('SITE_LANGUAGE', 'zh-cn');
        define('SITE_ID', 1);
        define('IS_API_HTTP', 0);
        define('THEME_PATH', '/static/');
        define('LANG_PATH', '/config/language/'.SITE_LANGUAGE.'/'); // 语言包
        if (!is_file(MYPATH.'Config/Version.php')) {
            exit('版本控制文件（'.MYPATH.'Config/Version.php'.'）不存在');
        }
        $app = require MYPATH.'Config/Version.php';
        define('DR_CMS', $app['id']);
        define('DR_NAME', $app['name']);
        define('DR_VERSION', $app['version']);
        \Phpcmf\Service::V()->init('pc');
        \Phpcmf\Service::V()->admin();
    }

    public function index() {

        $step = intval($_GET['step']);

        if (DR_CMS == 10) {
            !$step && $step = 1;
        }

        switch ($step) {

            case 0:
                break;

            case 1:

                $error = 0;
                // 目录权限检查
                $dir = [
                    WRITEPATH,
                    WEBPATH.'config/',
                    WEBPATH.'uploadfile/',
                ];
                $path = [];
                foreach ($dir as $t) {
                    $path[$t] = dr_check_put_path($t);
                    if (!$path[$t]) {
                        $error = 1;
                    }
                }
				$php = [];
				$php['mb string扩展'] = function_exists('mb_substr') ? 1 : 0;
				$php['Curl扩展'] = function_exists('curl_init') ? 1 : 0;
				
                \Phpcmf\Service::V()->assign([
                    'php' => $php,
                    'path' => $path,
                    'error' => $error,
                ]);
                break;

            case 2:

                // 安装信息填写
                if (IS_AJAX_POST) {

                    $data = $_POST['data'];
                    if (empty($data['name'])) {
                        $this->_json(0, '网站名称不能为空');
                    } elseif (empty($data['username'])) {
                        $this->_json(0, '创始人账号不能为空');
                    } elseif (empty($data['password'])) {
                        $this->_json(0, '创始人密码不能为空');
                    }  elseif (empty($data['email'])) {
                        $this->_json(0, '创始人邮箱不能为空');
                    } elseif (empty($data['db_host'])) {
                        $this->_json(0, '数据库地址不能为空');
                    } elseif (empty($data['db_user'])) {
                        $this->_json(0, '数据库账号不能为空');
                    } elseif (empty($data['db_name'])) {
                        $this->_json(0, '数据库名称不能为空');
                    } elseif (empty($data['db_prefix'])) {
                        $this->_json(0, '数据表前缀不能为空');
                    } elseif (is_numeric($data['db_name'])) {
                        exit($this->_json(0, '数据库名称不能是数字'));
                    } elseif (strpos($data['db_name'], '.') !== false) {
                        exit($this->_json(0, '数据库名称不能存在.号'));
                    }

                    $mysqli = function_exists('mysqli_init') ? mysqli_init() : 0;
                    if (!$mysqli) {
                        exit($this->_json(0, '您的PHP环境必须启用Mysqli扩展'));
                    }
                    if (!@mysqli_real_connect($mysqli, $data['db_host'], $data['db_user'], $data['db_pass'])) {
                        exit($this->_json(0, '[mysqli_real_connect] 无法连接到数据库服务器（'.$data['db_host'].'），请检查用户名（'.$data['db_user'].'）和密码（'.$data['db_pass'].'）是否正确'));
                    }
                    if (!@mysqli_select_db($mysqli, $data['db_name'])) {
                        if (!@mysqli_query($mysqli, 'CREATE DATABASE '.$data['db_name'])) {
                            exit($this->_json(0, '指定的数据库（'.$data['db_name'].'）不存在，系统尝试创建失败，请通过其他方式建立数据库'));
                        }
                    }

                    // 存储缓存文件中
                    $size = @file_put_contents(WRITEPATH.'install.info', dr_array2string($data));
                    if (!$size || $size < 10) {
                        $this->_json(0, '临时数据存储失败，cahce目录无法写入');
                    }

                    $data['db_prefix'] = strtolower($data['db_prefix']);

                    // 存储mysql
                    $database = '<?php

/**
 * 数据库配置文件
 */

$db[\'default\']	= [
    \'hostname\'	=> \''.$data['db_host'].'\',
    \'username\'	=> \''.$data['db_user'].'\',
    \'password\'	=> \''.$data['db_pass'].'\',
    \'database\'	=> \''.$data['db_name'].'\',
    \'DBPrefix\'	=> \''.dr_safe_filename($data['db_prefix']).'\',
];';
                    $size = @file_put_contents(WEBPATH.'config/database.php', $database);
                    if (!$size || $size < 10) {
                        $this->_json(0, '数据库配置文件创建失败，config目录无法写入');
                    }

                    $this->_json(1, 'index.php?c=install&m=index&is_install_db='.intval($_POST['is_install_db']).'&step='.($step+1));
                }


                break;

            case 3:

                $error = '';
                $data = dr_string2array(file_get_contents(WRITEPATH.'install.info'));
                file_put_contents(WRITEPATH.'install.error', '');
                if (empty($data)) {
                    $error = '临时数据获取失败，请返回前一页重新执行';
                } else {
                    $this->db = \Config\Database::connect('default');
                    // 检查数据库是否存在
                    if (!$this->db->connect(false)) {
                        // 链接失败,尝试创建数据库
                        $error = '数据库连接失败，请返回前一页重新执行';
                    } else {
                        // 导入默认安装数据
                        $sql = file_get_contents(CMSPATH.'Config/Install.sql');
                        $sql = str_replace('{dbprefix}', $data['db_prefix'], $sql);
                        $this->query($sql);
                        $errorlog = file_get_contents(WRITEPATH.'install.error');
                        if (strlen($errorlog) > 10) {
                            // 出现错误了
                            $error = $errorlog;
                        } else {
                            // 创建账号
                            $salt = substr(md5(rand(0, 999)), 0, 10);
                            $password = md5(md5($data['password']).$salt.md5($data['password']));
                            $this->db->table('member')->insert([
                                'email' => $data['email'],
                                'username' => $data['username'],
                                'password' => $password,
                                'salt' => $salt,
                                'name' => '创始人',
                                'phone' => '',
                                'money' => 1000000,
                                'freeze' => 0,
                                'spend' => 0,
                                'score' => 1000000,
                                'experience' => 1000000,
                                'regip' => '',
                                'regtime' => SYS_TIME,
                                'randcode' => 0,
                            ]);
                            $id = $this->db->insertID();
                            $this->db->table('member_data')->insert([
                                'id' => $id,
                                'is_lock' => 0,
                                'is_admin' => 1,
                                'is_auth' => 1,
                                'is_verify' => 1,
                                'is_mobile' => 1,
                                'is_complete' => 1,
                            ]);
                            // 加入管理员表
                            $this->db->table('admin')->insert([
                                'uid' => $id,
                                'setting' => '',
                                'usermenu' => '',
                            ]);
                            // 加入角色表
                            $this->db->table('admin_role_index')->insert([
                                'uid' => $id,
                                'roleid' => 1,
                            ]);
                            // 创建站点
                            \Phpcmf\Service::M('Site')->create([
                                'name' => $data['name'],
                                'domain' => DOMAIN_NAME,
                            ]);

                            // 写配置文件
                            $sys = [
                                'SYS_DEBUG'                     => '0', //调试器开关
                                'SYS_ADMIN_CODE'                => '0', //后台登录验证码开关
                                'SYS_ADMIN_LOG'                 => '0', //后台操作日志开关
                                'SYS_AUTO_FORM'                 => '0', //自动存储表单数据
                                'SYS_ADMIN_PAGESIZE'            => '10', //后台数据分页显示数量
                                'SYS_CAT_RNAME'                 => '0', //栏目目录允许重复
                                'SYS_PAGE_RNAME'                => '0', //单页目录允许重复
                                'SYS_KEY'                       => 'PHPCMF'.md5($data['name'].rand(1, 999999)), //安全密匙
                                'SYS_HTTPS'                     => '0', //https模式
                                'SYS_ATTACHMENT_DB'             => '', //附件归属开启模式
                                'SYS_ATTACHMENT_PATH'           => '', //附件上传路径
                                'SYS_ATTACHMENT_URL'            => '', //附件访问地址
                                'SYS_EMAIL' => $data['email'],
                            ];
                            \Phpcmf\Service::M('System')->save_config($sys, $sys);

                            // 执行安装程序
                            $sql = file_get_contents(MYPATH.'Config/Install.sql');
                            $sql = str_replace('{dbprefix}', $data['db_prefix'], $sql);
                            if (is_file(MYPATH.'Config/Install_site.sql')) {
                                $s = file_get_contents(MYPATH.'Config/Install_site.sql');
                                $sql.= PHP_EOL.str_replace('{dbprefix}', $data['db_prefix'].'1_', $s);
                            }
                            $this->query($sql);
                            if (is_file(MYPATH.'Config/Install.php')) {
                                require MYPATH.'Config/Install.php';
                            }
                            // 创建后台默认菜单
                            \Phpcmf\Service::M('Menu')->init('admin');
                            \Phpcmf\Service::M('Menu')->init('member');

                            // 删除app的install.lock
                            $local = dr_dir_map(dr_get_app_list(), 1);
                            foreach ($local as $dir) {
                                $path = dr_get_app_dir($dir);
                                if (is_file($path.'install.lock')) {
                                    @unlink($path.'install.lock');
                                }
                            }

                            // 完成之后更新缓存
                            \Phpcmf\Service::M('cache')->update_cache();
                            $errorlog = file_get_contents(WRITEPATH.'install.error');
                            if ($errorlog && count($errorlog) > 10) {
                                // 出现错误了
                                $error = $errorlog;
                            } else {
                                // 安装完成
                                file_put_contents($this->lock, time());
                                @unlink(WRITEPATH.'install.info');
                                @unlink(WRITEPATH.'install.error');
                            }
                        }
                    }
                }

                break;


        }
        \Phpcmf\Service::V()->assign([
            'step' => $step,
            'error' => $error,
            'pre_url' => 'index.php?c=install&m=index&step='.($step-1),
            'next_url' => 'index.php?c=install&m=index&step='.($step+1),
        ]);
        \Phpcmf\Service::V()->display('install/'.$step.'.html');
        exit;
    }


    // 数据执行
    private function query($sql) {

        if (!$sql) {
            return '';
        }

        $sql_data = explode(';SQL_FINECMS_EOL', trim(str_replace(array(PHP_EOL, chr(13), chr(10)), 'SQL_FINECMS_EOL', $sql)));

        foreach($sql_data as $query){
            if (!$query) {
                continue;
            }
            $ret = '';
            $queries = explode('SQL_FINECMS_EOL', trim($query));
            foreach($queries as $query) {
                $ret.= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
            }
            if (!$ret) {
                continue;
            }
            if (!$this->db->simpleQuery($ret)) {
                $rt = $this->db->error();
                $error = '**************************************************************************'
                    .PHP_EOL.$ret.PHP_EOL.$rt['message'].PHP_EOL;
                $error.= '**************************************************************************'.PHP_EOL;
                file_put_contents(WRITEPATH.'install.error', $error.PHP_EOL, FILE_APPEND);
            }
        }
    }

}