<?php namespace My\Field\Tag;

class Tag extends \Phpcmf\Library\A_Field {

    /**
     * 构造函数
     */
    public function __construct(...$params) {
        parent::__construct(...$params);
        $this->fieldtype = TRUE;
        $this->defaulttype = 'TEXT';
    }

    /**
     * 字段相关属性参数
     *
     * @param	array	$value	值
     * @return  string
     */
    public function option($option) {

        return [
            '
			<div class="form-group">
				<label class="col-md-2 control-label">'.dr_lang('控件宽度').'</label>
				<div class="col-md-9">
					<label><input type="text" class="form-control" size="10" name="data[setting][option][width]" value="'.$option['width'].'"></label>
					<span class="help-block">'.dr_lang('[整数]表示固定宽度；[整数%]表示百分比').'</span>
				</div>
			</div>
			
			',
            ''
        ];
    }


    /**
     * 字段表单输入
     *
     * @param	string	$field	字段数组
     * @param	array	$value	值
     * @return  string
     */
    public function input($field, $value = null) {

        // 字段禁止修改时就返回显示字符串
        if ($this->_not_edit($field, $value)) {
            return $this->show($field, $value);
        }

        // 字段默认值
        $value = dr_strlen($value) ? $value : $this->get_default_value($field['setting']['option']['value']);

        // 字段存储名称
        $name = $field['fieldname'];

        // 字段显示名称
        $text = ($field['setting']['validate']['required'] ? '<span class="required" aria-required="true"> * </span>' : '').dr_lang($field['name']);

        // 表单宽度设置
        $width = \Phpcmf\Service::IS_MOBILE_USER() ? '100%' : ($field['setting']['option']['width'] ? $field['setting']['option']['width'] : 400);

        // 风格
        $style = 'style="width:'.$width.(is_numeric($width) ? 'px' : '').';"';

        // 表单附加参数
        $attr = $field['setting']['validate']['formattr'];

        $css = '';
        if ($field['setting']['option']['not_input']) {
            $css.= '';
        }

        // 字段提示信息
        $tips = $field['setting']['validate']['tips'] ? '<span class="help-block" id="dr_'.$field['fieldname'].'_tips">'.$field['setting']['validate']['tips'].'</span>' : '';

        // 当字段必填时，加入html5验证标签
        $required =  $field['setting']['validate']['required'] ? ' required="required"' : '';


        $area = \Phpcmf\Service::IS_MOBILE_USER() ? '["95%", "90%"]' : '["50%", "65%"]';
        $ipt = '<input '.$style.' data-role="tagsinput" class="form-control '.$field['setting']['option']['css'].'" type="text" name="data['.$field['fieldname'].']" id="dr_'.$field['fieldname'].'" value="'.$value.'" '.$required.' '.$attr.' />';
        $str = '
				<label><a style="margin-top: -10px;" class="btn btn-success " href="javascript:dr_add_tag_'.$name.'();" ><i class="fa fa-plus" /></i> </a></label>
				<label>'.$ipt.'</label>'.$css.'
				<script>
				function dr_add_tag_'.$name.'() {
                    var url = "'.dr_web_prefix('index.php?s=tag&c=api&m=index&').'&pagesize='.intval($field['setting']['option']['pagesize']).'&is_iframe=1";
                    layer.open({
                        type: 2,
                        title: \'<i class="fa fa-cog"></i> '.dr_lang('关联').'\',
                        fix:true,
                        shadeClose: true,
                        shade: 0,
                        area: '.$area.',
                        btn: ["'.dr_lang('关联').'"],
                        success: function (json) {
                            if (json.code == 0) {
                                layer.close();
                                dr_tips(json.code, json.msg);
                            }
                        },
                        yes: function(index, layero){
                            var body = layer.getChildFrame(\'body\', index);
                             // 延迟加载
                            var loading = layer.load(2, {
                                time: 10000
                            });
                            $.ajax({type: "POST",dataType:"json", url: url, data: $(body).find(\'#myform\').serialize(),
                                success: function(json) {
                                    layer.close(loading);
                                    if (json.code == 1) {
                                        layer.close(index);
                                       
                                        for(var i in json.data){
                                            var vid = json.data[i];
                                            if (typeof vid != "undefined") {
                                                $("#dr_' . $name . '").tagsinput("add", vid);
                                            }
                                        }
                                        dr_tips(1, json.msg);
                                    } else {
                                        dr_tips(0, json.msg);
                
                                    }
                                    return false;
                                }
                            });
                            return false;
                        },
                        content: url
                    });
				}
</script>
		';

        return $this->input_format($field['fieldname'], $text, $str.$tips);
    }

}