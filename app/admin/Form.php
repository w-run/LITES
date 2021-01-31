<?php


namespace app\admin;


class Form
{
    public static function field($id, $el, $label = null, $attr = [])
    {
        return [
            'id' => $id,
            'el' => $el,
            'label' => $label,
            'attr' => $attr
        ];
    }
}