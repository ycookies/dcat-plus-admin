
<style>
    .flag-icons-box .fi{font-size:20px;margin: 5px 10px;}
</style>
<div class="flag-icons-box">
    <div class="row">
        <div class="col-md-6">
            @foreach($country_arr as $item)
                <br/><i class="fi fi-{{$item['code']}}"></i>{{$item['name']}}
            @endforeach
        </div>
        <div class="col-md-6">
            @foreach($country_cn_arr as $item)
                <br/><i class="fi fi-{{$item['code']}}"></i>{{$item['name']}}
            @endforeach
        </div>
    </div>

</div>
<script>
    Dcat.ready(function () {
        var clipboard3 = new ClipboardJS('.flag-icons-box .fi', {
            text: function (trigger) {
                return trigger.getAttribute('class');
            }
        });
        clipboard3.on('success', function (e) {
            e.clearSelection();
            layer.msg('已复制');
        });
        clipboard3.on('error', function (e) {
            e.clearSelection();
            layer.msg('复制内容失败');
        });
    });
</script>