<style>
 .has-many-box fieldset {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 10px;
  margin: 10px 0;
}
.has-many-box legend {
  font-weight: bold;
  padding: 0 5px;
}
.has-many-box .fields-group{
    background-color: #eff1f780;
    border-radius: 5px 5px 0 0;
}
</style>
<div class="has-many-box">
    <fieldset>
        <legend class="w-auto">{!! $label !!}</legend>
        
        
        <div class="has-many-{{$columnClass}}">
        
            <div class="has-many-{{$columnClass}}-forms">
        
                @foreach($forms as $pk => $form)
        
                    <div class="has-many-{{$columnClass}}-form fields-group">
        
                        {!! $form->render() !!}
        
                        @if($options['allowDelete'])
                            <div class="form-group row">
                                <label class="{{$viewClass['label']}} control-label"></label>
                                <div class="{{$viewClass['field']}}">
                                    <div class="{{$columnClass}}-remove btn btn-white btn-sm pull-right"><i class="feather icon-trash">&nbsp;</i>{{ trans('admin.remove') }}</div>
                                </div>
                            </div>
                        @endif
                        <hr>
                    </div>
        
                @endforeach
            </div>
        
        
            <template class="{{$columnClass}}-tpl">
                <div class="has-many-{{$columnClass}}-form fields-group">
        
                    {!! $template !!}
        
                    <div class="form-group row">
                        <label class="{{$viewClass['label']}} control-label"></label>
                        <div class="{{$viewClass['field']}}">
                            <div class="{{$columnClass}}-remove btn btn-white btn-sm pull-right"><i class="feather icon-trash"></i>&nbsp;{{ trans('admin.remove') }}</div>
                        </div>
                    </div>
                    <hr>
                </div>
            </template>
        
            @if($options['allowCreate'])
                <div class="form-group row">
                    <label class="{{$viewClass['label']}} control-label"></label>
                    <div class="{{$viewClass['field']}}">
                        <div class="{{$columnClass}}-add btn btn-primary btn-outline btn-sm"><i class="feather icon-plus"></i>&nbsp;{{ trans('admin.new') }}</div>
                    </div>
                </div>
            @endif
        </fieldset>
        </div>
</div>


<script>
    var nestedIndex = {!! $count !!},
        container = '.has-many-{{ $columnClass }}',
        forms = '.has-many-{{ $columnClass  }}-forms';

    function replaceNestedFormIndex(value) {
        return String(value)
            .replace(/{{ Dcat\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}/g, nestedIndex)
            .replace(/{{ Dcat\Admin\Form\NestedForm::DEFAULT_PARENT_KEY_NAME }}/g, nestedIndex);
    }

    $(container).on('click', '.{{$columnClass}}-add', function () {
        var tpl = $('template.{{ $columnClass }}-tpl');

        nestedIndex++;

        $(forms).append(replaceNestedFormIndex(tpl.html()));
    });

    $(container).on('click', '.{{$columnClass}}-remove', function () {
        var $form = $(this).closest('.has-many-{{ $columnClass }}-form');

        $form.hide();
        $form.find('.{{ Dcat\Admin\Form\NestedForm::REMOVE_FLAG_CLASS }}').val(1);
        $form.find('[required]').prop('required', false);
    });
</script>
