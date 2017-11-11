<?php
/**
 * Article Protect
 * 
 * @package ArticleProtect 
 * @author arest
 * @version 1.3.0
 * @link https://qintianxiang.com
 */
class ArticleProtect_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('ArticleProtect_Plugin', 'check');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('ArticleProtect_Plugin', 'check');
        Typecho_Plugin::factory('admin/write-post.php')->advanceOption = array('ArticleProtect_Plugin', 'protectpanel');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 自定义字段名称 */  
        $name = new Typecho_Widget_Helper_Form_Element_Text('chkField', NULL, _t("protectme"), _t('被保护文章的自定义字段,只用于限制未登录用户，更多权限在发表文章时设置【选项-高级选项-阅读权限】'));
        $form->addInput($name);
        $authmsg = new Typecho_Widget_Helper_Form_Element_Text('authmsg', NULL, _t("无权限访问此内容"), _t('无权限访问提示'));
        $form->addInput($authmsg);
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function check($content, $widget, $last)
    {
        $content = empty($last) ? $content : $last;
        if (self::_checkAuth($widget)) {
            return self::_format($content, $widget);
        } else {
            return Typecho_Widget::widget('Widget_Options')->plugin('ArticleProtect')->authmsg;
        }
    }

    public static function protectpanel($post) {
        $html = '<section class="typecho-post-option allow-option">';
        $html .= '<label class="typecho-label">阅读权限</label><ul>';
        $usr = Typecho_Widget::widget('Widget_User');
        $roles = $usr->groups;
        foreach($roles as $name=>$value) {
            $checked = "";
            $fname = "kgsoft_apcr_".$name;
            if (isset($post->fields->$fname)) {
                unset($post->fields->$fname);
                $checked = "checked";
            }
            $html .= "<li><input id='fields[kgsoft_apcr_$name]' name='fields[kgsoft_apcr_$name]' type='checkbox' value='$value' ".$checked."/><label for='fields[kgsoft_apcr_$name]'>$name</label></li>";
        }
        $html .= "</ul></section>";
        $html .= "<script>";
        $html .= "try{var ct = document.getElementById('custom-field').getElementsByTagName('table')[0].getElementsByTagName('tbody')[0];";
        $html .= "var cf = document.getElementById('custom-field').getElementsByTagName('tr');";
        $html .= "for (var i = cf.length - 1; i >= 0; i--) {if (cf[i].getElementsByTagName('input')[0].value.indexOf('kgsoft_apcr_') == 0) {ct.removeChild(cf[i])}}";
        $html .= "}catch(e){}</script>";
        echo $html;
    }

    private static function _checkAuth($widget) {
        $chkField = Typecho_Widget::widget('Widget_Options')->plugin('ArticleProtect')->chkField;
        $bSet = $widget->fields->__isSet($chkField);
        $user = $widget->widget('Widget_User');
        if ($bSet) {
            return $user->hasLogin();
        }
        $fields = $widget->fields;
        $authgroups = array();
        foreach ($fields as $fname=>$fval) {
            if (strpos("kgsoft_apcr_", $fname) == 0) {
                // 12 == strlen("kgsoft_apcr_")
                $group = substr($fname, 12);
                if (! $user->pass($group, true)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function _format($content, $widget) {
        return $widget->isMarkdown ? $widget->markdown($content)
                : $widget->autoP($content);
    }
}
