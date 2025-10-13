<?php

function dr_form_status_name($value, $param = [], $data = [], $field = []) {
    if ($value == 0) {
        //待审核
        return '<span class="label label-success"> '.dr_lang('待审核').' </span>';
    } elseif ($value == 1) {
        //已通过
        return '<span class="label label-success"> '.dr_lang('已通过').' </span>';
    } else {
        //未通过
        return '<span class="label label-danger"> '.dr_lang('未通过').' </span>';
    }
}