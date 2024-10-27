<div class="">
    <style >
        .icon-list-demo .col-md-3{
            margin: 3px;
        }
        .icon-list-demo span{
            font-size: 16px;
            font-weight: bold;
        }
    </style>
    <div class="alert alert-info alert-dismissable">
        <h4><i class="fa fa-info"></i>&nbsp; 使用说明</h4>
    </div>
    <section class="container">
        <h4 class="m-t-0 page-header header-title">字体颜色</h4>
        <div class="icon-list-demo row">
            <div class="col-md-3 bg-light"><span class="text-s text-primary"> .text-primary</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-pink"> .text-pink</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-purple"> .text-purple</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-cyan"> .text-cyan</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-cyan-darker"> .text-cyan-darker</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-info"> .text-info</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-blue-darker"> .text-blue-darker</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-blue-1"> .text-blue-1</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-blue-2"> .text-blue-2</span></div>
            <hr/>
            <div class="col-md-3 bg-light"><span class="text-s text-blue2"> .text-blue2</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-custom"> .text-custom</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-success"> .text-success</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-danger"> .text-danger</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-danger-darker"> .text-danger-darker</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-warning"> .text-warning</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-orange-1"> .text-orange-1</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-orange-2"> .text-orange-2</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-yellow"> .text-yellow</span></div>

            <div class="col-md-3 bg-light"><span class="text-s text-dark"> .text-dark</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-tear"> .text-tear</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-tear-1"> .text-tear-1</span></div>
            <div class="col-md-3 bg-light"><span class="text-s text-gray"> .text-gray</span></div>
            <div class="col-md-3 "><span class="text-s text-20"> .text-20</span></div>
            <div class="col-md-3 "><span class="text-s text-30"> .text-30</span></div>
            <div class="col-md-3 "><span class="text-s text-40"> .text-40</span></div>
            <div class="col-md-3 "><span class="text-s text-50"> .text-50</span></div>
            <div class="col-md-3 "><span class="text-s text-60"> .text-60</span></div>
            <div class="col-md-3 "><span class="text-s text-70"> .text-70</span></div>
            <div class="col-md-3 "><span class="text-s text-80"> .text-80</span></div>
            <div class="col-md-3 "><span class="text-s text-90"> .text-90</span></div>
        </div>
    </section>

</div>
<script>
    Dcat.ready(function () {
        var clipboard4 = new ClipboardJS('.text-s', {
            text: function (trigger) {
                var tes;
                tes =  trigger.getAttribute('class');
                return tes.replace("text-s ", "");
            }
        });
        clipboard4.on('success', function (e) {
            e.clearSelection();
            layer.msg('已复制');
        });
        clipboard4.on('error', function (e) {
            e.clearSelection();
            layer.msg('复制内容失败');
        });
    });
</script>