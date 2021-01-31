<?php
/*  
 *  @file FormField.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-24
 */

namespace app\admin;


class Form
{
    public static function field($id, $el, $label=null, $attr=[])
    {
        return [
            'id'=>$id,
            'el'=>$el,
            'label'=>$label,
            'attr'=>$attr
        ];
    }
}