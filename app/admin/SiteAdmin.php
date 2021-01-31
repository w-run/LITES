<?php
/*  
 *  @file UserAdmin.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-24
 */

namespace app\admin;

use core\lib\File;

class SiteAdmin extends DaoAdmin
{

    protected $menu_desc = [
        'common'=>"站点信息"
    ];

    public function common()
    {
        $desc = [
            'name'=>'系统名称',
            'domain'=>'服务端域名',
            'version'=>'版本信息',
            'allow_host'=>'客户端地址',
        ];
        $config = File::getJson(CONFIG_FILE);
        $site = $config['system'];
        $field = [];
        foreach ($site as $k => $v)
            array_push($field,Form::field($k,'input',$desc[$k]));
        $this->callback = $this->callback_form($field,$site);
    }

    public function edit()
    {

        $config = File::getJson(CONFIG_FILE);
        $config_backup = $config;
        File::write('core/conf/config.backup',json_encode($config));

        $data = $this->getData('data');
        foreach ($data as $k => $v)
            $config['system'][$k] = $v;
        $s = false;
        if(json_encode($config_backup)!=json_encode($config)){
            File::write('core/conf/config.json',json_encode($config));
            $s = true;
        }
        $this->callback = $this->callback_result($s, 'handle error', "reload");
    }


}